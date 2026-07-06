<?php

namespace App\Services;

class LanguageMapper
{
    protected static array $mappings = [
        // ==================== Auth ====================
        'تم التسجيل بنجاح. يرجى التحقق من بريدك الإلكتروني.' => 'auth.register_success',
        'تم التسجيل بنجاح. يرجى التحقق من بريدك الإلكتروني أولاً، ثم سيتم مراجعة حسابك من قبل الإدارة. سيتم إعلامك بالبريد عند الموافقة.' => 'auth.register_pending',
        'تم تسجيل الدخول بنجاح' => 'auth.login_success',
        'تم تسجيل الخروج بنجاح' => 'auth.logout_success',
        'بيانات الدخول غير صحيحة' => 'auth.invalid_credentials',
        'يرجى التحقق من بريدك الإلكتروني أولاً' => 'auth.email_not_verified',
        'حسابك قيد المراجعة من قبل الإدارة. سيتم إعلامك عند الموافقة.' => 'auth.account_pending_review',
        'حسابك معلق. يرجى التواصل مع الإدارة.' => 'auth.account_suspended',
        'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني' => 'auth.password_reset_sent',
        'تم إعادة تعيين كلمة المرور بنجاح' => 'auth.password_reset_success',
        'تم التحقق من بريدك الإلكتروني بنجاح!' => 'auth.email_verified',
        'تم إعادة إرسال رابط التحقق' => 'auth.verification_link_sent',
        'بيانات المزود غير مكتملة' => 'auth.incomplete_provider_data',
        'بيانات الطالب غير مكتملة' => 'auth.incomplete_student_data',
        'بيانات المشرف غير مكتملة' => 'auth.incomplete_supervisor_data',
        'التوكن غير صالح أو منتهي الصلاحية' => 'auth.token_invalid',
        'التوكن صالح' => 'auth.token_valid',
        'تم رفض حسابك.' => 'auth.account_rejected',
        'يرجى التحقق من بريدك الإلكتروني. تم إرسال رابط التحقق إليك.' => 'auth.verification_notice',
        'رابط التحقق غير صالح أو منتهي الصلاحية' => 'auth.invalid_verification_link',
        'المستخدم غير موجود' => 'auth.user_not_found',
        'رابط التحقق غير صالح' => 'auth.invalid_link',
        'البريد الإلكتروني موثق بالفعل' => 'auth.already_verified',
        'بريدك الإلكتروني موثق بالفعل' => 'auth.already_verified_message',
        'تم إرسال رابط التحقق مرة أخرى' => 'auth.verification_resent',

        // ==================== Opportunity ====================
        'تم إنشاء الفرصة بنجاح' => 'opportunity.created',
        'تم تحديث الفرصة بنجاح' => 'opportunity.updated',
        'تم إغلاق الفرصة بنجاح' => 'opportunity.closed',
        'تم إعادة فتح الفرصة بنجاح' => 'opportunity.reopened',
        'الفرصة لم تعد متاحة' => 'opportunity.not_available',
        'لقد قدمت على هذه الفرصة مسبقاً' => 'opportunity.already_applied',
        'يمكن إعادة فتح الفرصة المغلقة فقط' => 'opportunity.cannot_reopen_closed',
        'لا يمكن إعادة فتح الفرصة - الموعد النهائي قد انتهى' => 'opportunity.deadline_passed',
        'حسابك قيد المراجعة أو مرفوض. لا يمكنك نشر فرص تدريبية حتى تتم الموافقة على حسابك.' => 'opportunity.account_not_active',
        'حسابك غير نشط. لا يمكنك تعديل الفرص.' => 'opportunity.cannot_modify',
        'حسابك غير نشط. لا يمكنك إغلاق الفرص.' => 'opportunity.cannot_close',
        'حسابك غير نشط. لا يمكنك إعادة فتح الفرص.' => 'opportunity.cannot_reopen',

        // ==================== Application ====================
        'تم التقديم بنجاح' => 'application.submitted',
        'تم الانسحاب بنجاح' => 'application.withdrawn',
        'تم تحديث حالة التقديم' => 'application.status_updated',
        'لا توجد بيانات تقديم لهذا الطالب لدى مؤسستك' => 'application.no_application_found',

        // ==================== Report ====================
        'تم إرسال التقرير بنجاح' => 'report.submitted',
        'تم مراجعة التقرير' => 'report.reviewed',
        'لا يوجد طلاب تابعين لك حالياً' => 'report.no_students',
        'لا يوجد طلاب مسندين إليك حالياً' => 'report.no_assigned_students',
        'حدث خطأ في حفظ التقرير:' => 'report.save_error_prefix',

        // ==================== Evaluation ====================
        'تم حفظ التقييم' => 'evaluation.saved',
        'غير مصرح - هذه الفرصة لا تابعة لك' => 'evaluation.unauthorized_opportunity',
        'غير مصرح - هذا الطالب ليس تابعاً لك' => 'evaluation.unauthorized_student',
        'الطالب غير مقبول في هذه الفرصة' => 'evaluation.student_not_accepted',
        'الطالب غير موجود' => 'evaluation.student_not_found',
        'الفرصة غير موجودة' => 'evaluation.opportunity_not_found',
        'لم يتم قبول هذا الطالب في هذه الفرصة بعد. يجب أن يكون التقديم بحالة accepted.' => 'evaluation.student_not_accepted_yet',
        'لم يكتمل التقييم بعد. يجب أن يكون هناك تقييم نهائي من المزود والمشرف.' => 'evaluation.incomplete_evaluation',
        'تم حساب التقييم النهائي وإنشاء السجل بنجاح' => 'evaluation.final_calculated',
        'لا توجد سجلات تقييم بعد' => 'evaluation.no_records',

        // ==================== Message ====================
        'تم إرسال الرسالة' => 'message.sent',
        'تم التعليم كمقروءة' => 'message.marked_read',

        // ==================== Notification ====================
        'الإشعار غير موجود' => 'notification.not_found',
        'تم تعليم الإشعار كمقروء' => 'notification.marked_read',
        'تم تعليم جميع الإشعارات كمقروءة' => 'notification.all_marked_read',
        'تم حذف الإشعار' => 'notification.deleted',
        'تم حذف جميع الإشعارات' => 'notification.all_deleted',

        // ==================== AI ====================
        'تم تحسين التقرير بنجاح باستخدام الذكاء الاصطناعي.' => 'ai.improve_success',
        'تم تحليل التقرير بنجاح باستخدام الذكاء الاصطناعي.' => 'ai.analyze_success',
        'تم توليد التقرير بنجاح باستخدام الذكاء الاصطناعي.' => 'ai.generate_success',
        'تم توليد الاقتراحات بنجاح باستخدام الذكاء الاصطناعي.' => 'ai.suggest_success',
        'لم يتمكن الذكاء الاصطناعي من تحسين النص.' => 'ai.failed_improve',
        'لم يتمكن الذكاء الاصطناعي من تحليل التقرير.' => 'ai.failed_analyze',
        'لم يتمكن الذكاء الاصطناعي من توليد التقرير.' => 'ai.failed_generate',
        'لم يتمكن الذكاء الاصطناعي من توليد الاقتراحات.' => 'ai.failed_suggest',
        'تم تحليل التقرير بنجاح.' => 'ai.analyze_default',
        'تم توليد الاقتراحات بنجاح.' => 'ai.suggest_default',
        'حدث خطأ أثناء الاتصال بالذكاء الاصطناعي:' => 'ai.connection_error_prefix',
        'محتوى جيد' => 'ai.default_strength_content',
        'تنظيم واضح' => 'ai.default_strength_structure',
        'يحتاج لمزيد من التفاصيل' => 'ai.default_weakness_details',
        'أضف أمثلة عملية' => 'ai.default_improvement_examples',
        'استخدم مصطلحات تقنية' => 'ai.default_improvement_terms',
        'التقرير جيد بشكل عام ولكن يمكن تحسينه بإضافة المزيد من التفاصيل التقنية والأمثلة العملية.' => 'ai.default_feedback',
        'إليك النسخة المحسنة' => 'ai.intro_improved',
        'إليك التقرير' => 'ai.intro_report',
        'تعلم أساسيات المجال' => 'ai.default_topic_1',
        'مراجعة الوثائق التقنية' => 'ai.default_topic_2',
        'المشاركة في اجتماعات الفريق' => 'ai.default_topic_3',
        'قراءة الوثائق' => 'ai.default_task_1',
        'حضور اجتماع' => 'ai.default_task_2',
        'تنفيذ مهمة بسيطة' => 'ai.default_task_3',
        'فهم بيئة العمل' => 'ai.default_challenge_1',
        'التعامل مع أدوات جديدة' => 'ai.default_challenge_2',
        'التواصل المهني' => 'ai.default_skill_1',
        'إدارة الوقت' => 'ai.default_skill_2',
        'اذكر التحديات والحلول' => 'ai.default_tip_2',
        'تعلمت أساسيات المجال' => 'ai.default_example_1',
        'شاركت في اجتماع الفريق' => 'ai.default_example_2',

        // ==================== Validation ====================
        'محتوى التقرير مطلوب.' => 'validation.content_required',
        'يجب أن يحتوي التقرير على 10 أحرف على الأقل.' => 'validation.content_min',
        'يجب إدخال نقاط التقرير.' => 'validation.points_required',
        'يجب إدخال نقطتين على الأقل.' => 'validation.points_min',
        'الحد الأقصى 20 نقطة.' => 'validation.points_max',
        'كل نقطة يجب أن تحتوي على نص.' => 'validation.point_required',
        'كل نقطة يجب أن تحتوي على 3 أحرف على الأقل.' => 'validation.point_min',
        'التخصص مطلوب.' => 'validation.major_required',

        // ==================== Admin ====================
        'تمت الموافقة على حساب المزود بنجاح' => 'admin.provider_approved',
        'تم رفض حساب المزود' => 'admin.provider_rejected',
        'المزود غير موجود' => 'admin.provider_not_found',
        'الحساب موثق بالفعل' => 'admin.already_approved',
        'تم تعيين المشرف بنجاح' => 'admin.supervisor_assigned',
        'تم تحديث السجل' => 'admin.record_updated',

        // ==================== Certificate ====================
        'تم إصدار الشهادة بنجاح' => 'certificate.generated',
        'الشهادة غير موجودة' => 'certificate.not_found',
        'غير مؤهل للحصول على شهادة' => 'certificate.not_eligible',

        // ==================== General ====================
        'غير مصرح' => 'general.unauthorized',

        'حدث خطأ:' => 'general.error_prefix',

                // ==================== General Errors ====================
        'غير مصرح - يرجى تسجيل الدخول أولاً' => 'general.unauthorized_login',
        'ليس لديك صلاحية للوصول إلى هذا المورد' => 'general.forbidden',
        'بيانات غير صالحة' => 'general.validation_failed',
        'المسار غير موجود' => 'general.not_found',
        'حدث خطأ في الخادم' => 'general.server_error',
        'تم تحديث اللغة بنجاح' => 'general.language_updated',

                // ==================== Admin User Management ====================
        'تم إنشاء المستخدم بنجاح' => 'admin.user_created',
        'تم تحديث المستخدم بنجاح' => 'admin.user_updated',
        'تم حذف المستخدم بنجاح' => 'admin.user_deleted',
        'تم تعليق حساب المستخدم' => 'admin.user_suspended',
        'تم تفعيل حساب المستخدم' => 'admin.user_activated',
        'لا يمكنك حذف حسابك الخاص' => 'admin.cannot_delete_self',
        'لا يمكنك تعليق حسابك الخاص' => 'admin.cannot_suspend_self',
    ];

    public static function getKey(string $text): ?string
    {
        $text = trim($text);

        if (isset(self::$mappings[$text])) {
            return self::$mappings[$text];
        }

        foreach (self::$mappings as $arabic => $key) {
            if (str_contains($arabic, $text) || str_contains($text, $arabic)) {
                return $key;
            }
        }

        return null;
    }

    public static function getAllMappings(): array
    {
        return self::$mappings;
    }

    public static function addMapping(string $arabic, string $key): void
    {
        self::$mappings[$arabic] = $key;
    }
}
