<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Support\Feedback;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class FeedbackStack extends Component
{
    /** @var array<string, mixed>|null */
    public readonly ?array $initialFeedback;

    /**
     * @param  array<string, mixed>|null  $initialFeedback
     */
    public function __construct(?array $initialFeedback = null)
    {
        $this->initialFeedback = $initialFeedback ?? Feedback::fromSession(
            session()->pull('rutx_feedback'),
        );
    }

    public function render(): View
    {
        return view('components.feedback-stack');
    }
}
