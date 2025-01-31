<?php

return [

    'accepted'             => ':attribute يجب أن يكون مقبولاً.',
    'accepted_if'          => ':attribute يجب أن يكون مقبولاً عندما يكون :other هو :value.',
    'active_url'           => ':attribute ليس عنوان URL صالحًا.',
    'after'                => ':attribute يجب أن يكون تاريخًا بعد :date.',
    'after_or_equal'       => ':attribute يجب أن يكون تاريخًا بعد أو يساوي :date.',
    'alpha'                => ':attribute يجب أن يحتوي على حروف فقط.',
    'alpha_dash'           => ':attribute يجب أن يحتوي على حروف وأرقام وشرطات.',
    'alpha_num'            => ':attribute يجب أن يحتوي على حروف وأرقام فقط.',
    'array'                => ':attribute يجب أن يكون مصفوفة.',
    'ascii'                => ':attribute يجب أن يحتوي على أحرف ASCII فقط.',
    'as_array'             => ':attribute يجب أن يكون مصفوفة.',
    'at_least'             => [
        'numeric' => ':attribute يجب أن يكون على الأقل :min.',
        'file'    => ':attribute يجب أن يكون على الأقل :min كيلوبايت.',
        'string'  => ':attribute يجب أن يكون على الأقل :min حرفًا.',
        'array'   => ':attribute يجب أن يحتوي على الأقل :min عنصرًا.',
    ],
    'before'               => ':attribute يجب أن يكون تاريخًا قبل :date.',
    'before_or_equal'      => ':attribute يجب أن يكون تاريخًا قبل أو يساوي :date.',
    'between'              => [
        'numeric' => ':attribute يجب أن يكون بين :min و :max.',
        'file'    => ':attribute يجب أن يكون بين :min و :max كيلوبايت.',
        'string'  => ':attribute يجب أن يكون بين :min و :max حرفًا.',
        'array'   => ':attribute يجب أن يحتوي على بين :min و :max عنصرًا.',
    ],
    'boolean'              => ':attribute يجب أن يكون صحيحًا أو خطأً.',
    'confirmed'            => ':attribute تأكيد التطابق غير مطابق.',
    'current_password'     => 'كلمة المرور الحالية غير صحيحة.',
    'date'                 => ':attribute ليس تاريخًا صالحًا.',
    'date_equals'          => ':attribute يجب أن يكون تاريخًا مساويًا لـ :date.',
    'date_format'          => ':attribute لا يتطابق مع التنسيق :format.',
    'decimal'              => ':attribute يجب أن يحتوي على أرقام عشرية.',
    'declined'             => ':attribute يجب أن يتم رفضه.',
    'declined_if'          => ':attribute يجب أن يتم رفضه عندما يكون :other هو :value.',
    'different'            => ':attribute و :other يجب أن يكونا مختلفين.',
    'digits'               => ':attribute يجب أن يكون :digits أرقام.',
    'digits_between'       => ':attribute يجب أن يكون بين :min و :max أرقام.',
    'dimensions'           => ':attribute أبعاد صورة غير صالحة.',
    'distinct'             => ':attribute يحتوي على قيمة مكررة.',
    'doesnt_end_with'      => ':attribute يجب ألا ينتهي بأي من التالي: :values.',
    'doesnt_start_with'    => ':attribute يجب ألا يبدأ بأي من التالي: :values.',
    'email'                => ':attribute يجب أن يكون عنوان بريد إلكتروني صالحًا.',
    'ends_with'            => ':attribute يجب أن ينتهي بأحد الخيارات التالية: :values.',
    'enum'                 => ':attribute القيمة المحددة غير صالحة.',
    'exists'               => ':attribute المحدد غير صالح.',
    'file'                 => ':attribute يجب أن يكون ملفًا.',
    'filled'               => ':attribute مطلوب.',
    'gt'                   => [
        'numeric' => ':attribute يجب أن يكون أكبر من :value.',
        'file'    => ':attribute يجب أن يكون أكبر من :value كيلوبايت.',
        'string'  => ':attribute يجب أن يكون أكبر من :value حرفًا.',
        'array'   => ':attribute يجب أن يحتوي على أكثر من :value عنصر.',
    ],
    'gte'                  => [
        'numeric' => ':attribute يجب أن يكون أكبر من أو يساوي :value.',
        'file'    => ':attribute يجب أن يكون أكبر من أو يساوي :value كيلوبايت.',
        'string'  => ':attribute يجب أن يكون أكبر من أو يساوي :value حرفًا.',
        'array'   => ':attribute يجب أن يحتوي على :value عناصر أو أكثر.',
    ],
    'image'                => ':attribute يجب أن تكون صورة.',
    'in'                   => ':attribute المحدد غير صالح.',
    'in_array'             => ':attribute غير موجود في :other.',
    'integer'              => ':attribute يجب أن يكون عددًا صحيحًا.',
    'ip'                   => ':attribute يجب أن يكون عنوان IP صالحًا.',
    'ipv4'                 => ':attribute يجب أن يكون عنوان IPv4 صالحًا.',
    'ipv6'                 => ':attribute يجب أن يكون عنوان IPv6 صالحًا.',
    'is'                   => ':attribute غير صالح.',
    'json'                 => ':attribute يجب أن يكون سلسلة JSON صالحة.',
    'lt'                   => [
        'numeric' => ':attribute يجب أن يكون أقل من :value.',
        'file'    => ':attribute يجب أن يكون أقل من :value كيلوبايت.',
        'string'  => ':attribute يجب أن يكون أقل من :value حرفًا.',
        'array'   => ':attribute يجب أن يحتوي على أقل من :value عنصر.',
    ],
    'lte'                  => [
        'numeric' => ':attribute يجب أن يكون أقل من أو يساوي :value.',
        'file'    => ':attribute يجب أن يكون أقل من أو يساوي :value كيلوبايت.',
        'string'  => ':attribute يجب أن يكون أقل من أو يساوي :value حرفًا.',
        'array'   => ':attribute يجب أن يحتوي على :value عناصر أو أقل.',
    ],
    'mac_address'          => ':attribute يجب أن يكون عنوان MAC صالحًا.',
    'max'                  => [
        'numeric' => ':attribute يجب ألا يكون أكبر من :max.',
        'file'    => ':attribute يجب ألا يكون أكبر من :max كيلوبايت.',
        'string'  => ':attribute يجب ألا يكون أكبر من :max حرفًا.',
        'array'   => ':attribute يجب ألا يحتوي على أكثر من :max عنصر.',
    ],
    'max_digits'           => ':attribute يجب ألا يحتوي على أكثر من :max أرقام.',
    'mimes'                => ':attribute يجب أن يكون ملفًا من النوع: :values.',
    'mimetypes'            => ':attribute يجب أن يكون ملفًا من النوع: :values.',
    'min'                  => [
        'numeric' => ':attribute يجب أن يكون على الأقل :min.',
        'file'    => ':attribute يجب أن يكون على الأقل :min كيلوبايت.',
        'string'  => ':attribute يجب أن يكون على الأقل :min حرفًا.',
        'array'   => ':attribute يجب أن يحتوي على الأقل :min عنصر.',
    ],
    'min_digits'           => ':attribute يجب أن يحتوي على الأقل :min أرقام.',
    'missing'              => ':attribute مفقود.',
    'missing_if'           => ':attribute مفقود عندما يكون :other هو :value.',
    'missing_unless'       => ':attribute مفقود ما لم يكن :other هو :value.',
    'missing_with'         => ':attribute مفقود مع :values.',
    'missing_with_all'     => ':attribute مفقود مع :values.',
    'multiple_of'          => ':attribute يجب أن يكون مضاعفًا لـ :value.',
    'not_in'               => ':attribute المحدد غير صالح.',
    'not_regex'            => ':attribute نمط غير صالح.',
    'numeric'              => ':attribute يجب أن يكون رقمًا.',
    'password'             => 'كلمة المرور غير صحيحة.',
    'phone'                => ':attribute رقم هاتف غير صحيح.',
    'present'              => ':attribute يجب أن يكون موجودًا.',
    'prohibited'           => ':attribute محظور.',
    'prohibited_if'        => ':attribute محظور عندما يكون :other هو :value.',
    'prohibited_unless'    => ':attribute محظور ما لم يكن :other هو :value.',
    'prohibits'            => ':attribute يحظر :other.',
    'regex'                => ':attribute نمط غير صالح.',
    'required'             => ':attribute مطلوب.',
    'required_if'          => ':attribute مطلوب عندما يكون :other هو :value.',
    'required_unless'      => ':attribute مطلوب ما لم يكن :other موجودًا.',
    'required_with'        => ':attribute مطلوب عند وجود :values.',

    'required_with_all'    => ':attribute مطلوب عند وجود جميع :values.',
    'required_without'     => ':attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => ':attribute مطلوب عند عدم وجود أي من :values.',
    'same'                 => ':attribute و :other يجب أن يكونا متطابقين.',
    'size'                 => [
        'numeric' => ':attribute يجب أن يكون :size.',
        'file'    => ':attribute يجب أن يكون :size كيلوبايت.',
        'string'  => ':attribute يجب أن يكون :size حرفًا.',
        'array'   => ':attribute يجب أن يحتوي على :size عنصر.',
    ],
    'starts_with'          => ':attribute يجب أن يبدأ بأحد الخيارات التالية: :values.',
    'string'               => ':attribute يجب أن يكون نصًا.',
    'timezone'             => ':attribute يجب أن تكون منطقة زمنية صحيحة.',
    'unique'               => ':attribute تم أخذه بالفعل.',
    'uploaded'             => ':attribute فشل في التحميل.',
    'url'                  => ':attribute يجب أن يكون عنوان URL صالحًا.',
    'uuid'                 => ':attribute يجب أن يكون UUID صالحًا.',
    'validate_phone_number' => ':attribute رقم هاتف غير صحيح.', // Example for custom rule

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'mobile' => 'رقم الجوال',
        'province_id' => 'المحافظة',
        'city_id' => 'المدينة',
        'user_type' => 'نوع المستخدم',
        'address_line_1' => 'سطر العنوان الأول',
        'address_line_2' => 'سطر العنوان الثاني',
        'zip_code' => 'الرمز البريدي',
    ],
    'mobile' => ':attribute رقم هاتف غير صحيح.',

];
