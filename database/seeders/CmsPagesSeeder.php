<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => ['en' => 'About Us', 'ar' => 'من نحن'],
                'slug' => 'about-us',
                'content' => [
                    'en' => '<h2>About ESEVEN STORE</h2><p>Welcome to ESEVEN STORE — the largest shoe store in Saudi Arabia. We offer premium men\'s and women\'s footwear and clothing that suits your unique style.</p><p>Our mission is to bring you the latest trends and the best brands at competitive prices, with an exceptional shopping experience.</p>',
                    'ar' => '<h2>عن اي سفن ستور</h2><p>مرحباً بكم في اي سفن ستور — أكبر متجر أحذية في المملكة العربية السعودية. نقدم أحذية وملابس رجالية ونسائية مميزة تناسب ذوقك الفريد.</p><p>مهمتنا هي تقديم أحدث الصيحات وأفضل العلامات التجارية بأسعار تنافسية، مع تجربة تسوق استثنائية.</p>',
                ],
                'excerpt' => ['en' => 'Learn more about ESEVEN STORE.', 'ar' => 'تعرف على المزيد عن اي سفن ستور.'],
                'template' => 'default',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'title' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
                'slug' => 'privacy-policy',
                'content' => [
                    'en' => '<h2>Privacy Policy</h2><p>Your privacy is important to us. This policy explains how we collect, use, and protect your personal information when you use our website and services.</p><h3>Information We Collect</h3><p>We collect information you provide directly, such as your name, email, phone number, and shipping address when you create an account or place an order.</p><h3>How We Use Your Information</h3><p>We use your information to process orders, improve our services, and communicate with you about your purchases and promotions.</p>',
                    'ar' => '<h2>سياسة الخصوصية</h2><p>خصوصيتك مهمة بالنسبة لنا. توضح هذه السياسة كيف نجمع معلوماتك الشخصية ونستخدمها ونحميها عند استخدامك لموقعنا وخدماتنا.</p><h3>المعلومات التي نجمعها</h3><p>نجمع المعلومات التي تقدمها مباشرة، مثل اسمك وبريدك الإلكتروني ورقم هاتفك وعنوان الشحن عند إنشاء حساب أو تقديم طلب.</p><h3>كيف نستخدم معلوماتك</h3><p>نستخدم معلوماتك لمعالجة الطلبات وتحسين خدماتنا والتواصل معك بشأن مشترياتك والعروض الترويجية.</p>',
                ],
                'excerpt' => ['en' => 'Our privacy policy.', 'ar' => 'سياسة الخصوصية الخاصة بنا.'],
                'template' => 'legal',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'title' => ['en' => 'Exchange and Return Policy', 'ar' => 'سياسة الاستبدال والاسترجاع'],
                'slug' => 'exchange-return-policy',
                'content' => [
                    'en' => '<h2>Exchange and Return Policy</h2><p>We want you to be completely satisfied with your purchase. If you are not happy with your order, you may return or exchange it within the specified period.</p><h3>Return Conditions</h3><p>Items must be in their original condition, unworn, and with all tags attached. Returns must be initiated within 14 days of delivery.</p><h3>How to Return</h3><p>Contact our customer service team via WhatsApp or email to initiate a return or exchange request.</p>',
                    'ar' => '<h2>سياسة الاستبدال والاسترجاع</h2><p>نريدك أن تكون راضياً تماماً عن مشترياتك. إذا لم تكن سعيداً بطلبك، يمكنك إرجاعه أو استبداله خلال الفترة المحددة.</p><h3>شروط الإرجاع</h3><p>يجب أن تكون المنتجات في حالتها الأصلية، غير مستخدمة، مع جميع البطاقات. يجب بدء الإرجاع خلال 14 يوماً من التسليم.</p><h3>كيفية الإرجاع</h3><p>تواصل مع فريق خدمة العملاء عبر واتساب أو البريد الإلكتروني لبدء طلب الإرجاع أو الاستبدال.</p>',
                ],
                'excerpt' => ['en' => 'Our exchange and return policy.', 'ar' => 'سياسة الاستبدال والاسترجاع.'],
                'template' => 'legal',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'title' => ['en' => 'Terms and Conditions', 'ar' => 'الشروط والأحكام'],
                'slug' => 'terms-conditions',
                'content' => [
                    'en' => '<h2>Terms and Conditions</h2><p>By using our website and services, you agree to the following terms and conditions. Please read them carefully before making a purchase.</p><h3>General Terms</h3><p>All products displayed on our website are subject to availability. Prices are in Saudi Riyals and may change without prior notice.</p><h3>Order Processing</h3><p>Orders are processed within 1-3 business days. You will receive a confirmation email once your order has been shipped.</p>',
                    'ar' => '<h2>الشروط والأحكام</h2><p>باستخدامك لموقعنا وخدماتنا، فإنك توافق على الشروط والأحكام التالية. يرجى قراءتها بعناية قبل إجراء عملية شراء.</p><h3>الشروط العامة</h3><p>جميع المنتجات المعروضة على موقعنا تخضع للتوفر. الأسعار بالريال السعودي وقد تتغير دون إشعار مسبق.</p><h3>معالجة الطلبات</h3><p>تتم معالجة الطلبات خلال 1-3 أيام عمل. ستتلقى بريداً إلكترونياً للتأكيد بمجرد شحن طلبك.</p>',
                ],
                'excerpt' => ['en' => 'Terms and conditions of use.', 'ar' => 'شروط وأحكام الاستخدام.'],
                'template' => 'legal',
                'status' => 'active',
                'sort_order' => 4,
            ],
            [
                'title' => ['en' => 'Tamara Payment Service', 'ar' => 'خدمة الدفع تمارا'],
                'slug' => 'tamara-payment',
                'content' => [
                    'en' => '<h2>Tamara Payment Service</h2><p>Shop now and pay later with Tamara! Split your purchase into easy installments with no interest and no hidden fees.</p><h3>How It Works</h3><p>Select Tamara at checkout, split your payment into 3 installments, and enjoy your purchase immediately. Sharia compliant and no late fees.</p>',
                    'ar' => '<h2>خدمة الدفع تمارا</h2><p>تسوق الآن وادفع لاحقاً مع تمارا! قسّم مشترياتك إلى أقساط سهلة بدون فوائد وبدون رسوم خفية.</p><h3>كيف تعمل</h3><p>اختر تمارا عند الدفع، قسّم الدفعة إلى 3 أقساط، واستمتع بمشترياتك فوراً. متوافق مع الشريعة وبدون رسوم تأخير.</p>',
                ],
                'excerpt' => ['en' => 'Pay in installments with Tamara.', 'ar' => 'ادفع بالأقساط مع تمارا.'],
                'template' => 'default',
                'status' => 'active',
                'sort_order' => 5,
            ],
            [
                'title' => ['en' => 'Affiliate Marketing Program', 'ar' => 'برنامج التسويق بالعمولة'],
                'slug' => 'affiliate',
                'content' => [
                    'en' => '<h2>Affiliate Marketing Program</h2><p>Join our affiliate program and earn commissions by promoting ESEVEN STORE products. Share your unique link and earn from every successful purchase.</p><h3>How to Join</h3><p>Contact our team to register as an affiliate partner and receive your unique referral link.</p>',
                    'ar' => '<h2>برنامج التسويق بالعمولة</h2><p>انضم إلى برنامج التسويق بالعمولة واكسب عمولات عند الترويج لمنتجات اي سفن ستور. شارك رابطك الفريد واكسب من كل عملية شراء ناجحة.</p><h3>كيفية الانضمام</h3><p>تواصل مع فريقنا للتسجيل كشريك تسويق بالعمولة واحصل على رابط الإحالة الفريد الخاص بك.</p>',
                ],
                'excerpt' => ['en' => 'Join our affiliate program.', 'ar' => 'انضم إلى برنامج التسويق بالعمولة.'],
                'template' => 'default',
                'status' => 'active',
                'sort_order' => 6,
            ],
        ];

        foreach ($pages as $pageData) {
            CmsPage::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }
    }
}
