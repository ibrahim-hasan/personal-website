<?php

namespace Tests\Feature;

use App\Mail\ConsultationRequestMail;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\AtharAccessCodeNotification;
use App\Notifications\AtharApprovalNotification;
use App\Notifications\AtharInvitationNotification;
use App\Notifications\AtharStatusNotification;
use App\Notifications\Auth\AdminResetPasswordNotification;
use App\Notifications\Auth\ReaderResetPasswordNotification;
use App\Notifications\Auth\ReaderVerifyEmailNotification;
use App\Notifications\CommentApprovedNotification;
use App\Notifications\CommentReplyNotification;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class BrandedEmailRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_arabic_notification_emails_use_the_branded_rtl_shell(): void
    {
        $reader = User::factory()->create([
            'name' => 'سارة عبدالله',
            'email' => 'sara@example.com',
            'locale_preference' => 'ar',
        ]);
        $replyAuthor = User::factory()->create(['name' => 'أحمد محمد']);
        $article = Article::factory()->create([
            'slug' => ['ar' => 'رسالة-البريد', 'en' => 'email-rendering'],
        ]);
        $comment = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $reply = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $replyAuthor->getKey(),
            'parent_id' => $comment->getKey(),
        ]);

        $adminReset = app(FilamentResetPasswordNotification::class, ['token' => 'admin-reset-token']);
        $adminReset->url = 'https://ibrahimhasan.test/admin/password-reset/reset';

        $this->assertInstanceOf(AdminResetPasswordNotification::class, $adminReset);

        $messages = [
            (new ReaderVerifyEmailNotification)->toMail($reader),
            (new ReaderResetPasswordNotification('reader-reset-token'))->toMail($reader),
            $adminReset->toMail($reader),
            (new CommentApprovedNotification($comment))->toMail($reader),
            (new CommentReplyNotification($reply))->toMail($reader),
        ];

        foreach ($messages as $message) {
            $this->assertBrandedDirection($this->renderMailMessage($message, 'ar'), 'ar', 'rtl', 'right');
        }

        $verificationHtml = $this->renderMailMessage(
            (new ReaderVerifyEmailNotification)->toMail($reader),
            'ar',
        );

        $this->assertStringContainsString('أكّد هذا البريد لإكمال إعداد حساب القارئ.', $verificationHtml);
        $this->assertStringContainsString('تأكيد البريد الإلكتروني', $verificationHtml);
    }

    public function test_english_notification_emails_use_the_branded_ltr_shell(): void
    {
        $reader = User::factory()->create([
            'name' => 'Olivia Reader',
            'email' => 'olivia@example.com',
            'locale_preference' => 'en',
        ]);

        $verification = (new ReaderVerifyEmailNotification)->toMail($reader);
        $reset = (new ReaderResetPasswordNotification('reader-reset-token'))->toMail($reader);

        $this->assertBrandedDirection($this->renderMailMessage($verification, 'en'), 'en', 'ltr', 'left');
        $this->assertBrandedDirection($this->renderMailMessage($reset, 'en'), 'en', 'ltr', 'left');

        $this->assertStringContainsString('Confirm this email address to finish setting up your reader account.', $this->renderMailMessage($verification, 'en'));
        $this->assertStringContainsString('/en/reader/reset-password/', $this->renderMailMessage($reset, 'en'));
    }

    public function test_athar_email_notifications_carry_their_explicit_locale_into_the_shared_shell(): void
    {
        $recipient = new AnonymousNotifiable;
        $arabicNotifications = [
            new AtharAccessCodeNotification('123456', 'ar'),
            new AtharInvitationNotification('https://ibrahimhasan.test/athar/invitation', 'ar'),
            new AtharApprovalNotification('https://ibrahimhasan.test/athar/approval', 'ar'),
            new AtharStatusNotification('تم حفظ رسالتك الخاصة.', 'ar'),
        ];

        foreach ($arabicNotifications as $notification) {
            $this->assertSame('ar', $notification->locale);
            $this->assertBrandedDirection($this->renderMailMessage($notification->toMail($recipient), 'ar'), 'ar', 'rtl', 'right');
        }

        $accessCodeHtml = $this->renderMailMessage(
            (new AtharAccessCodeNotification('123456', 'ar'))->toMail($recipient),
            'ar',
        );

        $this->assertStringContainsString('123456', $accessCodeHtml);

        $arabicInvitationHtml = $this->renderMailMessage(
            (new AtharInvitationNotification('https://ibrahimhasan.test/athar/invitation', 'ar'))->toMail($recipient),
            'ar',
        );

        $this->assertStringContainsString('أدعوك إلى كتابة ما تتذكّره من تجربة عملنا معاً بطريقتك. تصل رسالتك إلى إبراهيم بشكل خاص، ولا يظهر شيء منها على الموقع تلقائياً.', $arabicInvitationHtml);

        $englishInvitation = new AtharInvitationNotification('https://ibrahimhasan.test/en/athar/invitation', 'en');

        $this->assertSame('en', $englishInvitation->locale);
        $this->assertBrandedDirection($this->renderMailMessage($englishInvitation->toMail($recipient), 'en'), 'en', 'ltr', 'left');
    }

    public function test_consultation_mailable_locks_its_queued_locale_and_uses_the_shared_branded_shell(): void
    {
        $mail = new ConsultationRequestMail([
            'name' => 'Amira Noor',
            'email' => 'amira@example.com',
            'company' => 'Northstar Labs',
            'service' => 'strategy',
            'service_label' => 'Strategy and systems',
            'challenge' => 'A long operational challenge that should remain readable in the email brief.',
            'locale' => 'en',
        ]);

        $mail->assertHasSubject('New consultation request');
        $mail->assertSeeInHtml('New free consultation request');
        $mail->assertSeeInText('New free consultation request');

        $html = (string) $mail->render();

        $this->assertBrandedDirection($html, 'en', 'ltr', 'left');
        $this->assertStringContainsString('mailto:amira@example.com', $html);
        $this->assertStringContainsString('<bdi', $html);
        $this->assertStringContainsString('amira@example.com', $html);
        $this->assertStringContainsString('Northstar Labs', $html);
    }

    public function test_arabic_consultation_mailable_uses_a_rtl_inquiry_layout_with_ltr_contact_details(): void
    {
        $mail = new ConsultationRequestMail([
            'name' => 'أميرة نور',
            'email' => 'amira@example.com',
            'company' => 'نورث ستار',
            'service' => 'strategy',
            'service_label' => 'الاستراتيجية والأنظمة',
            'challenge' => 'نحتاج إلى اتخاذ قرار تقني أكثر وضوحاً.',
            'locale' => 'ar',
        ]);

        $mail->assertHasSubject('طلب استشارة جديدة');

        $html = (string) $mail->render();

        $this->assertBrandedDirection($html, 'ar', 'rtl', 'right');
        $this->assertStringContainsString('طلب استشارة مجانية جديد', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString('padding-left: 22px', $html);
        $this->assertStringContainsString('amira@example.com', $html);
    }

    public function test_email_wordmark_is_a_retina_asset_with_the_expected_intrinsic_dimensions(): void
    {
        $wordmarkPath = public_path('images/brand/ibrahim-email-wordmark.png');
        $image = getimagesize($wordmarkPath);

        $this->assertIsArray($image);
        $this->assertSame(756, $image[0]);
        $this->assertSame(160, $image[1]);
        $this->assertSame(IMAGETYPE_PNG, $image[2]);

        $wordmark = imagecreatefrompng($wordmarkPath);

        $this->assertNotFalse($wordmark);

        $topLeftPixel = imagecolorat($wordmark, 0, 0);
        $topLeftAlpha = ($topLeftPixel >> 24) & 0x7F;

        $this->assertGreaterThan(0, $topLeftAlpha);

        imagedestroy($wordmark);
    }

    private function assertBrandedDirection(string $html, string $locale, string $direction, string $alignment): void
    {
        $this->assertMatchesRegularExpression(
            '/<html xmlns="http:\/\/www\.w3\.org\/1999\/xhtml" lang="'.preg_quote($locale, '/').'" dir="'.preg_quote($direction, '/').'"(?: style="[^"]+")?>/',
            $html,
        );
        $this->assertStringContainsString('dir="'.$direction.'"', $html);
        $this->assertStringContainsString('align="'.$alignment.'"', $html);
        $this->assertStringContainsString('class="header-cell" dir="'.$direction.'" align="center"', $html);
        $this->assertStringContainsString('ibrahim-email-wordmark.png', $html);
        $this->assertStringContainsString('width="252" height="53"', $html);
        $this->assertStringContainsString('background-color: #190f32', $html);
        $this->assertStringContainsString('thmanyah-text-regular.woff2', $html);
        $this->assertStringContainsString('NotoSans-400.woff2', $html);
        $this->assertStringContainsString('box-sizing: border-box', $html);
        $this->assertStringContainsString('max-width: 100% !important', $html);
        $this->assertStringNotContainsString('Strategic technology practice', $html);
        $this->assertStringNotContainsString('ممارسة استراتيجية في التقنية', $html);

        if (str_contains($html, 'class="button ')) {
            $this->assertMatchesRegularExpression('/<table[^>]*width="100%"[^>]*border="0"/', $html);
        }

        if ($direction === 'rtl') {
            $this->assertStringContainsString('letter-spacing: normal', $html);
            $this->assertStringContainsString("font-family: 'Thmanyah Text'", $html);
        } else {
            $this->assertStringContainsString("font-family: 'Noto Sans'", $html);
        }

        $this->assertStringNotContainsString('Laravel Logo', $html);
    }

    private function renderMailMessage(MailMessage $message, string $locale): string
    {
        $originalLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            return (string) $message->render();
        } finally {
            app()->setLocale($originalLocale);
        }
    }
}
