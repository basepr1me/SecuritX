<?php

declare(strict_types=1);

namespace Laminas\Form\View\Helper\File;

use function ini_get;

/**
 * A view helper to render the hidden input with a Session progress id
 * for file uploads progress tracking.
 */
class FormFileSessionProgress extends FormFileUploadProgress
{
    protected function getName(): string
    {
        return ini_get('session.upload_progress.name');
    }
}
