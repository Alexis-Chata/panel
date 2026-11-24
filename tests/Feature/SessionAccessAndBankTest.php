<?php

use App\Imports\QuestionsImport;
use App\Livewire\JoinSession;
use App\Livewire\ManageSessions;
use App\Livewire\QuestionBank;
use App\Livewire\RunSession;
use App\Models\Answer;
use App\Models\DeviceLock;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\QuestionOption;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use App\Models\User;
use App\Support\SessionAnalytics;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Permission::create(['name' => 'sessions.export']);
    Role::create(['name' => 'Admin'])->givePermissionTo('sessions.export');
    Role::create(['name' => 'Docente'])->givePermissionTo('sessions.export');
    Role::create(['name' => 'Estudiante']);
});

function teacher(): User
{
    return tap(User::factory()->create())->assignRole('Docente');
}

function student(): User
{
    return tap(User::factory()->create())->assignRole('Estudiante');
}

/** Partida con 1 pregunta de alternativas (A correcta). */
function gameFor(?User $owner, array $attrs = []): GameSession
{
    $group = QuestionGroup::create(['name' => 'G', 'user_id' => $owner?->id]);
    $q = Question::create(['statement' => '<p>Hola &amp; adiós</p>', 'qtype' => 'multiple', 'question_group_id' => $group->id, 'user_id' => $owner?->id]);
    foreach (['A' => true, 'B' => false, 'C' => false, 'D' => false] as $i => $ok) {
        QuestionOption::create(['question_id' => $q->id, 'label' => $i, 'content' => $i, 'is_correct' => $ok, 'opt_order' => ord($i) - 64]);
    }

    $s = GameSession::create($attrs + [
        'code' => strtoupper(fake()->unique()->bothify('??????######')),
        'title' => 'Partida', 'questions_total' => 1, 'timer_default' => 30,
        'student_view_mode' => 'completo', 'user_id' => $owner?->id,
    ]);
    SessionQuestion::create(['game_session_id' => $s->id, 'question_id' => $q->id, 'q_order' => 0]);

    return $s;
}

/* ---------------- Dueño de las partidas ---------------- */

it('un docente no puede ejecutar ni exportar la partida de otro docente', function () {
    $owner = teacher();
    $other = teacher();
    $s = gameFor($owner);

    $this->actingAs($other)->get(route('sessions.run', $s))->assertForbidden();
    $this->actingAs($other)->get(route('screen.display', $s))->assertForbidden();
    $this->actingAs($other)->get(route('sessions.export.analytics.excel', $s))->assertForbidden();

    $this->actingAs($owner)->get(route('sessions.run', $s))->assertOk();
    $this->actingAs($owner)->get(route('sessions.export.analytics.pdf', $s))->assertOk();
});

it('las partidas antiguas (sin dueño) siguen accesibles para cualquier docente', function () {
    $s = gameFor(null);
    $this->actingAs(teacher())->get(route('sessions.run', $s))->assertOk();
});

it('un alumno no puede abrir la pantalla proyectada (muestra la respuesta correcta)', function () {
    $s = gameFor(teacher());
    $this->actingAs(student())->get(route('screen.display', $s))->assertForbidden();
});

it('la lista de partidas solo muestra las propias y las sin dueño', function () {
    $me = teacher();
    $mine = gameFor($me, ['title' => 'MIA']);
    gameFor(teacher(), ['title' => 'AJENA']);
    gameFor(null, ['title' => 'LEGADO']);

    Livewire::actingAs($me)->test(ManageSessions::class)
        ->assertSee('MIA')->assertSee('LEGADO')->assertDontSee('AJENA')
        ->call('toggleActive', GameSession::where('title', 'AJENA')->first()->id)
        ->assertForbidden();
});

it('crear y duplicar asignan el dueño', function () {
    $me = teacher();
    Livewire::actingAs($me)->test(ManageSessions::class)
        ->set('form.title', 'Nueva')
        ->call('save_session');

    $created = GameSession::where('title', 'Nueva')->first();
    expect($created->user_id)->toBe($me->id);

    $legacy = gameFor(null);
    Livewire::actingAs($me)->test(ManageSessions::class)->call('duplicateSession', $legacy->id);
    $copy = GameSession::where('title', 'Partida (Copia)')->first();
    expect($copy->user_id)->toBe($me->id)->and($copy->questions_total)->toBe(1);
});

it('editar el título de una partida en curso NO la hace retroceder', function () {
    $me = teacher();
    $s = gameFor($me, ['questions_total' => 1]);

    $c = Livewire::actingAs($me)->test(ManageSessions::class)->call('editar', $s->id);

    // Mientras el modal estaba abierto, la partida avanzó
    $s->update(['is_active' => true, 'is_running' => true, 'current_q_index' => 1, 'current_q_started_at' => now()]);

    $c->set('form.title', 'Renombrada')->call('save_session');

    $s->refresh();
    expect($s->title)->toBe('Renombrada')
        ->and($s->is_running)->toBeTrue()
        ->and($s->current_q_index)->toBe(1);
});

it('no permite recomponer preguntas de una partida con respuestas (las borraría)', function () {
    $me = teacher();
    $s = gameFor($me);
    $p = SessionParticipant::create(['game_session_id' => $s->id, 'user_id' => student()->id]);
    Answer::create(['session_participant_id' => $p->id, 'session_question_id' => $s->sessionQuestions()->first()->id, 'is_correct' => true]);

    Livewire::actingAs($me)->test(ManageSessions::class)
        ->call('openConfigureQuestions', $s->id)
        ->set('questionPickMode', 'random')
        ->set('compositionQuestionGroupId', (string) $s->sessionQuestions()->first()->question->question_group_id)
        ->call('save_question_composition')
        ->assertHasErrors('compositionQuestionGroupId');

    expect(Answer::count())->toBe(1);
});

/* ---------------- Ingreso y dispositivo ---------------- */

it('avisa al ingresar desde otro dispositivo y el docente puede liberarlo', function () {
    $owner = teacher();
    $s = gameFor($owner, ['is_active' => true]);
    $alumno = student();
    DeviceLock::create(['game_session_id' => $s->id, 'user_id' => $alumno->id, 'device_hash' => 'equipo-1']);

    Livewire::actingAs($alumno)->test(JoinSession::class)
        ->set('code', $s->code)->set('device_hash', 'equipo-2')
        ->call('join')
        ->assertSet('error', fn ($e) => str_contains((string) $e, 'otro dispositivo'))
        ->assertNoRedirect();

    $p = SessionParticipant::create(['game_session_id' => $s->id, 'user_id' => $alumno->id]);
    Livewire::actingAs($owner)->test(RunSession::class, ['gameSession' => $s])->call('releaseDevice', $p->id);

    Livewire::actingAs($alumno)->test(JoinSession::class)
        ->set('code', $s->code)->set('device_hash', 'equipo-2')
        ->call('join')
        ->assertRedirect(route('play', $s));
});

/* ---------------- Analítico ---------------- */

it('el analítico cuenta bien y limpia el HTML del enunciado', function () {
    $s = gameFor(teacher());
    $sq = $s->sessionQuestions()->first();
    $opts = $sq->question->options->keyBy('label');

    foreach (['A', 'A', 'B'] as $label) {
        $p = SessionParticipant::create(['game_session_id' => $s->id, 'user_id' => student()->id]);
        Answer::create([
            'session_participant_id' => $p->id, 'session_question_id' => $sq->id,
            'question_option_id' => $opts[$label]->id, 'is_correct' => $label === 'A',
        ]);
    }

    $row = SessionAnalytics::rows($s)->first();
    expect($row)->toMatchArray([
        'q' => 'Hola & adiós', 'correct' => 'A', 'answered' => 3, 'corrects' => 2,
        'acc' => 66.7, 'A' => 2, 'B' => 1, 'C' => 0, 'D' => 0,
    ]);
});

/* ---------------- Banco de preguntas ---------------- */

it('importa a la categoría elegida, y si una fila falla no importa ninguna', function () {
    $me = teacher();
    $group = QuestionGroup::create(['name' => 'Mate', 'user_id' => $me->id]);

    $ok = (new QuestionsImport($group->id, $me->id));
    $ok->collection(collect([
        ['statement' => '2+2', 'feedback' => '', 'a' => '4', 'b' => '5', 'c' => '6', 'd' => '7', 'correct' => 'a'],
        ['statement' => '', 'a' => '', 'b' => '', 'c' => '', 'd' => '', 'correct' => ''], // fila vacía: se ignora
    ]));
    expect($ok->imported)->toBe(1)
        ->and(Question::where('question_group_id', $group->id)->first()->options()->where('is_correct', true)->value('label'))->toBe('A');

    expect(fn () => (new QuestionsImport($group->id, $me->id))->collection(collect([
        ['statement' => 'Buena', 'a' => '1', 'b' => '2', 'c' => '3', 'd' => '4', 'correct' => 'B'],
        ['statement' => 'Mala', 'a' => '1', 'b' => '2', 'c' => '3', 'd' => '4', 'correct' => 'Z'],
    ])))->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(Question::count())->toBe(1);
});

it('editar una pregunta conserva los IDs de sus alternativas (historial de respuestas)', function () {
    $me = teacher();
    $s = gameFor($me);
    $q = $s->sessionQuestions()->first()->question;
    $idsBefore = $q->options()->orderBy('opt_order')->pluck('id')->all();

    $c = Livewire::actingAs($me)->test(QuestionBank::class)->call('openQuestionEdit', $q->id);
    $opts = $c->get('opts');
    $opts[1]['content'] = 'B editada';
    $c->set('opts', $opts)->call('setCorrectOption', 1)->call('saveQuestion')->assertHasNoErrors();

    expect($q->options()->orderBy('opt_order')->pluck('id')->all())->toBe($idsBefore)
        ->and($q->options()->where('is_correct', true)->value('label'))->toBe('B');
});

it('no borra una pregunta usada en partidas', function () {
    $me = teacher();
    $q = gameFor($me)->sessionQuestions()->first()->question;

    Livewire::actingAs($me)->test(QuestionBank::class)
        ->call('confirmQuestionDelete', $q->id)
        ->call('deleteQuestionNow');

    expect(Question::find($q->id))->not->toBeNull();
});

it('rechaza ciclos en la jerarquía de categorías', function () {
    $me = teacher();
    $a = QuestionGroup::create(['name' => 'A', 'user_id' => $me->id]);
    $b = QuestionGroup::create(['name' => 'B', 'user_id' => $me->id, 'parent_id' => $a->id]);

    Livewire::actingAs($me)->test(QuestionBank::class)
        ->call('openCategoryEdit', $a->id)
        ->set('cat_parent_id', $b->id)
        ->call('saveCategory')
        ->assertHasErrors('cat_parent_id');

    expect($a->fresh()->parent_id)->toBeNull();
});

/* ---------------- Subidas ---------------- */

it('la subida de CKEditor guarda en el disco público y bloquea a alumnos', function () {
    Storage::fake('public');

    $this->actingAs(student())
        ->post(route('ckeditor.upload'), ['upload' => UploadedFile::fake()->image('x.png')])
        ->assertForbidden();

    $res = $this->actingAs(teacher())
        ->post(route('ckeditor.upload'), ['upload' => UploadedFile::fake()->image('x.png')])
        ->assertOk();

    $file = 'questions/'.basename($res->json('url'));
    Storage::disk('public')->assertExists($file);
});
