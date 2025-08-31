<?php

namespace Modules\CoremailReplyPreprocess\Providers;

use Illuminate\Support\ServiceProvider;

class CoremailReplyPreprocessServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->hooks();
    }

    public function hooks()
    {
        \Eventy::addFilter(
            'fetch_emails.separate_reply.preprocess_body',
            static function (string $body): string {
                return \Modules\CoremailReplyPreprocess\Support\BodyPreprocessor::preprocess($body);
            },
            10,
            1,
        );
    }
}
