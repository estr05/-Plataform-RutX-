{{--
    Props:
    - initialFeedback: notificación interna normalizada por App\Support\Feedback.

    Este componente debe montarse una sola vez dentro de layouts/app.blade.php.
--}}
<div
    id="rutx-feedback-stack"
    class="rutx-feedback-stack"
    aria-live="polite"
    aria-relevant="additions"
    data-rutx-feedback-stack
    data-initial-feedback='@json($initialFeedback, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
></div>

@if ($initialFeedback !== null)
    <noscript>
        <div class="rutx-feedback-noscript" aria-label="Notificaciones del sistema">
            <x-alert :type="$initialFeedback['type']" :message="$initialFeedback['message']" />
        </div>
    </noscript>
@endif
