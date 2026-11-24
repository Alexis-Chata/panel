<?php

use App\Events\AnswerSubmitted;
use App\Events\GameSessionStateChanged;
use App\Livewire\PlayBasic;
use App\Livewire\RunSession;
use App\Models\Answer;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\QuestionOption;
use App\Models\SessionParticipant;
use App\Models\SessionQuestion;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/** Crea una partida corriendo con 2 preguntas (alternativa + corta) y N alumnos. */
function makeRunningGame(int $students = 2): array
{
    $group = QuestionGroup::create(['name' => 'G']);

    $q1 = Question::create(['statement' => '<p>2+2</p>', 'qtype' => 'multiple', 'question_group_id' => $group->id]);
    $ok = QuestionOption::create(['question_id' => $q1->id, 'label' => 'A', 'content' => '4', 'is_correct' => true, 'opt_order' => 1]);
    $bad = QuestionOption::create(['question_id' => $q1->id, 'label' => 'B', 'content' => '5', 'is_correct' => false, 'opt_order' => 2]);
    $q2 = Question::create(['statement' => 'Capital', 'qtype' => 'short', 'question_group_id' => $group->id]);

    $session = GameSession::create([
        'code' => 'ABC123', 'title' => 'T', 'questions_total' => 2, 'timer_default' => 30,
        'student_view_mode' => 'completo', 'question_group_id' => $group->id,
        'is_active' => true, 'is_running' => true, 'is_paused' => false,
        'current_q_index' => 0, 'current_q_started_at' => now(),
    ]);
    SessionQuestion::create(['game_session_id' => $session->id, 'question_id' => $q1->id, 'q_order' => 0]);
    SessionQuestion::create(['game_session_id' => $session->id, 'question_id' => $q2->id, 'q_order' => 1]);

    $users = User::factory()->count($students)->create();
    foreach ($users as $u) {
        SessionParticipant::create(['game_session_id' => $session->id, 'user_id' => $u->id, 'nickname' => $u->name]);
    }

    return compact('session', 'ok', 'bad', 'users');
}

beforeEach(fn () => Event::fake([AnswerSubmitted::class, GameSessionStateChanged::class]));

it('broadcasts AnswerSubmitted (antes no implementaba ShouldBroadcast)', function () {
    expect(new AnswerSubmitted(1, 1, 1))->toBeInstanceOf(ShouldBroadcast::class);
});

it('registra respuesta correcta, actualiza score y notifica al docente', function () {
    ['session' => $s, 'ok' => $ok, 'users' => $users] = makeRunningGame();

    Livewire::actingAs($users[0])->test(PlayBasic::class, ['gameSession' => $s])
        ->call('answer', $ok->id, 0)
        ->assertSet('answered_option_id', $ok->id);

    $p = SessionParticipant::where('user_id', $users[0]->id)->first();
    expect($p->score)->toBe(1)
        ->and(Answer::count())->toBe(1);
    Event::assertDispatched(AnswerSubmitted::class, fn ($e) => $e->answeredCount === 1 && $e->participantsCount === 2);
    expect($s->fresh()->is_paused)->toBeFalse();
});

it('rechaza opciones de otra pregunta y respuestas dobles', function () {
    ['session' => $s, 'ok' => $ok, 'users' => $users] = makeRunningGame();

    $c = Livewire::actingAs($users[0])->test(PlayBasic::class, ['gameSession' => $s]);
    $c->call('answer', 999999, 0);
    expect(Answer::count())->toBe(0);

    $c->call('answer', $ok->id, 0)->call('answer', $ok->id, 0);
    expect(Answer::count())->toBe(1);
});

it('rechaza respuestas fuera de tiempo', function () {
    ['session' => $s, 'ok' => $ok, 'users' => $users] = makeRunningGame();
    $s->update(['current_q_started_at' => now()->subSeconds(60)]);

    Livewire::actingAs($users[0])->test(PlayBasic::class, ['gameSession' => $s])
        ->call('answer', $ok->id, 0);

    expect(Answer::count())->toBe(0);
});

it('pausa (revela) una sola vez cuando responden todos', function () {
    ['session' => $s, 'ok' => $ok, 'bad' => $bad, 'users' => $users] = makeRunningGame();

    Livewire::actingAs($users[0])->test(PlayBasic::class, ['gameSession' => $s])->call('answer', $ok->id, 0);
    Livewire::actingAs($users[1])->test(PlayBasic::class, ['gameSession' => $s])->call('answer', $bad->id, 0);

    expect($s->fresh()->is_paused)->toBeTrue();
    Event::assertDispatchedTimes(GameSessionStateChanged::class, 1);
});

it('una pregunta corta sin respuesta (timeout) no bloquea al docente', function () {
    ['session' => $s, 'users' => $users] = makeRunningGame(1);
    $s->update(['current_q_index' => 1, 'current_q_started_at' => now()]);

    Livewire::actingAs($users[0])->test(PlayBasic::class, ['gameSession' => $s])->call('answer', null, 0);

    $a = Answer::first();
    expect($a->graded_at)->not->toBeNull()->and($a->is_correct)->toBeFalse();
});

it('el panel muestra métricas correctas y evita doble avance', function () {
    Role::create(['name' => 'Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    ['session' => $s, 'ok' => $ok, 'bad' => $bad, 'users' => $users] = makeRunningGame(3);
    Livewire::actingAs($users[0])->test(PlayBasic::class, ['gameSession' => $s])->call('answer', $ok->id, 0);
    Livewire::actingAs($users[1])->test(PlayBasic::class, ['gameSession' => $s])->call('answer', $bad->id, 0);

    $run = Livewire::actingAs($admin)->test(RunSession::class, ['gameSession' => $s])
        ->assertViewHas('pCount', 3)
        ->assertViewHas('answered', 2)
        ->assertViewHas('corrects', 1)
        ->assertViewHas('dist', fn ($d) => $d[0]['count'] === 1 && $d[1]['count'] === 1);

    // Revelar y avanzar; un segundo clic durante el conteo no debe hacer nada
    $run->call('revealAndPause')
        ->call('nextQuestion')->assertSet('countdownActive', true)
        ->call('nextQuestion')
        ->call('advanceNow', 1)
        ->call('advanceNow', 1); // conteo duplicado/obsoleto

    $s->refresh();
    expect($s->current_q_index)->toBe(1)->and($s->is_paused)->toBeFalse();
});

it('reanudar tras pausa no consume el tiempo pausado', function () {
    Role::create(['name' => 'Admin']);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    ['session' => $s] = makeRunningGame();

    $started = now()->subSeconds(10)->startOfSecond();
    $s->update(['current_q_started_at' => $started]);

    $run = Livewire::actingAs($admin)->test(RunSession::class, ['gameSession' => $s]);
    $this->travelTo(now());
    $run->call('togglePause');
    $this->travel(20)->seconds();
    $run->call('togglePause');

    $s->refresh();
    expect($s->is_paused)->toBeFalse()
        ->and($s->current_q_started_at->diffInSeconds($started->copy()->addSeconds(20)))->toBeLessThan(2);
});
