<?php
//namespace App\Http\Middleware;
//
//use App\Infrastructure\Helper\SessionHelper;
//use App\Infrastructure\Helper\TranslationHelper;
//
//// TODO finish translation
//class TranslationMiddleware
//{
//    public function handle(callable $next)
//    {
//        $successMessage = SessionHelper::get('success');
//        $errorMessage = SessionHelper::get('error');
//
//        if ($successMessage) {
//            $translatedSuccess = TranslationHelper::translate($successMessage, 'pl');
//            SessionHelper::set('success_translated', $translatedSuccess);
//        }
//
//        if ($errorMessage) {
//            $translatedError = TranslationHelper::translate($errorMessage, 'pl');
//            SessionHelper::set('error_translated', $translatedError);
//        }
//
//        return $next();
//    }
//}