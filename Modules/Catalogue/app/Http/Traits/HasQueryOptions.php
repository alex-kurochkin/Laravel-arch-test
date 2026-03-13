<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Traits;

use Illuminate\Http\Request;

trait HasQueryOptions
{
    final protected function getOptions(Request $request): array
    {
        $optionsParam = $request->query('options', '');

        if (empty($optionsParam)) {
            return [];
        }

        return array_map('trim', explode(',', $optionsParam));
    }

    final protected function hasOption(Request $request, string $option): bool
    {
        return in_array($option, $this->getOptions($request), true);
    }

    final protected function getOptionsAsString(Request $request): string
    {
        return $request->query('options', '');
    }
}
