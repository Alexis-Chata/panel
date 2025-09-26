<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['statement', 'feedback', 'qtype', 'meta'];

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function shortAnswers()
    {
        return $this->hasMany(QuestionShortAnswer::class);
    }

    public function evaluateShort(string $input): array
    {
        $cfg = array_replace(
            ['case_sensitive' => false, 'strip_accents' => true, 'max_distance' => 0],
            data_get($this->meta, 'short_answer', [])
        );

        $norm = function ($s) use ($cfg) {
            $s = trim($s);
            if (!$cfg['case_sensitive']) $s = \Illuminate\Support\Str::lower($s);
            if ($cfg['strip_accents'])   $s = \Illuminate\Support\Str::ascii($s);
            $s = preg_replace('/\s+/', ' ', $s);
            return trim($s, " \t\n\r\0\x0B\"'.,;:!?");
        };

        $u = $norm($input);
        foreach ($this->shortAnswers as $acc) {
            $a = $norm($acc->text);
            $d = levenshtein($u, $a);
            if ($cfg['max_distance'] === 0 ? $u === $a : $d <= $cfg['max_distance']) {
                $factor = [0 => 1, 1 => 0.75, 2 => 0.5, 3 => 0.25][$d] ?? 1;
                return ['ok' => true, 'score' => ($acc->weight / 100) * $factor, 'matched' => $acc->id];
            }
        }
        return ['ok' => false, 'score' => 0, 'matched' => null];
    }

    // 👇 Esto convierte array⇄json automáticamente
    protected $casts = [
        'meta' => 'array',
    ];
}
