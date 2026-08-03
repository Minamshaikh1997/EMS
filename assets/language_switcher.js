(function () {
    'use strict';

    if (window.__emsLanguageSwitcherLoaded) return;
    window.__emsLanguageSwitcherLoaded = true;

    const translations = {
        'Dashboard': 'ڈیش بورڈ', 'Employees': 'ملازمین', 'Add Employee': 'ملازم شامل کریں',
        'Employee Rights': 'ملازم کے حقوق', 'Leave Requests': 'چھٹی کی درخواستیں',
        'Supervisor Adjustments': 'سپروائزر ایڈجسٹمنٹس', 'Admin Adjustments': 'ایڈمن ایڈجسٹمنٹس',
        'Manage Shifts': 'شفٹس کا انتظام', 'Attendance': 'حاضری', 'Reports': 'رپورٹس',
        'Payroll Dashboard': 'پے رول ڈیش بورڈ', 'Generate Payroll': 'پے رول بنائیں',
        'Payroll History': 'پے رول ہسٹری', 'Salary Components': 'تنخواہ کے اجزاء',
        'Salary Slips': 'تنخواہ کی سلپس', 'Payroll Reports': 'پے رول رپورٹس',
        'Payroll Report': 'پے رول رپورٹ', 'Salary Structure': 'تنخواہ کا ڈھانچہ',
        'Monthly Payroll': 'ماہانہ پے رول', 'Notices': 'نوٹس', 'Holidays': 'چھٹیاں',
        'Send Email': 'ای میل بھیجیں', 'Security Audit': 'سیکیورٹی آڈٹ',
        'Change Password': 'پاس ورڈ تبدیل کریں', 'Logout': 'لاگ آؤٹ',
        'Back to Dashboard': 'ڈیش بورڈ پر واپس جائیں', 'Back': 'واپس', 'Save': 'محفوظ کریں',
        'Submit': 'جمع کریں', 'Cancel': 'منسوخ کریں', 'Delete': 'حذف کریں', 'Edit': 'ترمیم کریں',
        'Search': 'تلاش', 'Filter': 'فلٹر', 'Reset': 'ری سیٹ', 'Apply': 'درخواست دیں',
        'Approve': 'منظور کریں', 'Reject': 'مسترد کریں', 'Pending': 'زیر التوا',
        'Approved': 'منظور شدہ', 'Rejected': 'مسترد شدہ', 'Active': 'فعال', 'Inactive': 'غیر فعال',
        'Name': 'نام', 'Email': 'ای میل', 'Password': 'پاس ورڈ', 'Role': 'کردار',
        'Department': 'شعبہ', 'Designation': 'عہدہ', 'Status': 'حالت', 'Action': 'کارروائی',
        'Actions': 'کارروائیاں', 'Date': 'تاریخ', 'Start Date': 'شروع کی تاریخ',
        'End Date': 'اختتامی تاریخ', 'Reason': 'وجہ', 'Employee': 'ملازم',
        'Profile': 'پروفائل', 'Edit Profile': 'پروفائل میں ترمیم', 'Upload Photo': 'تصویر اپ لوڈ کریں',
        'Apply Leave': 'چھٹی کی درخواست', 'Leave History': 'چھٹی کی ہسٹری',
        'Leave Balance': 'چھٹی کا بیلنس', 'Attendance History': 'حاضری کی ہسٹری',
        'My Payroll': 'میرا پے رول', 'Check In': 'چیک اِن', 'Check Out': 'چیک آؤٹ',
        'Login': 'لاگ اِن', 'Sign In': 'سائن اِن', 'Admin Login': 'ایڈمن لاگ اِن',
        'Welcome': 'خوش آمدید', 'Main': 'مرکزی', 'System': 'سسٹم', 'Payroll': 'پے رول',
        'English': 'English', 'Urdu': 'اردو', 'Language': 'زبان'
    };

    const originalText = new WeakMap();
    const originalAttributes = new WeakMap();
    const ignored = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA', 'OPTION']);

    function translateText(value, language) {
        if (language === 'en') return value;
        const leading = value.match(/^\s*/)[0];
        const trailing = value.match(/\s*$/)[0];
        const clean = value.trim();
        if (!clean) return value;
        return leading + (translations[clean] || clean) + trailing;
    }

    function translateNode(node, language) {
        if (node.nodeType === Node.TEXT_NODE) {
            if (!node.parentElement || ignored.has(node.parentElement.tagName) || node.parentElement.closest('#ems-language-switcher')) return;
            if (!originalText.has(node)) originalText.set(node, node.nodeValue);
            const source = originalText.get(node);
            node.nodeValue = language === 'en' ? source : translateText(source, language);
            return;
        }
        if (node.nodeType !== Node.ELEMENT_NODE || node.closest('#ems-language-switcher')) return;
        ['placeholder', 'title', 'aria-label'].forEach(function (attribute) {
            if (!node.hasAttribute(attribute)) return;
            if (!originalAttributes.has(node)) originalAttributes.set(node, {});
            const saved = originalAttributes.get(node);
            if (!(attribute in saved)) saved[attribute] = node.getAttribute(attribute);
            node.setAttribute(attribute, language === 'en' ? saved[attribute] : translateText(saved[attribute], language));
        });
        Array.from(node.childNodes).forEach(function (child) { translateNode(child, language); });
    }

    function applyLanguage(language) {
        const selected = language === 'ur' ? 'ur' : 'en';
        document.documentElement.lang = selected;
        document.documentElement.dir = selected === 'ur' ? 'rtl' : 'ltr';
        document.body.classList.toggle('ems-rtl', selected === 'ur');
        translateNode(document.body, selected);
        const select = document.getElementById('ems-language-select');
        if (select) select.value = selected;
        try { localStorage.setItem('ems_language', selected); } catch (error) {}
    }

    function install() {
        const style = document.createElement('style');
        style.textContent = '#ems-language-switcher{position:fixed;left:18px;bottom:18px;z-index:2000;background:#fff;border:1px solid #dbe3ef;border-radius:999px;padding:7px 10px;box-shadow:0 8px 24px rgba(15,23,42,.18);font:600 13px Inter,Arial,sans-serif;color:#1e293b}#ems-language-switcher select{border:0;background:transparent;color:inherit;font:inherit;outline:0;cursor:pointer}.ems-rtl .sidebar-link,.ems-rtl .header-left,.ems-rtl .card-header-custom{text-align:right}.ems-rtl .sidebar-link{border-left:0;border-right:3px solid transparent}.ems-rtl .sidebar-link.active{border-right-color:#3b82f6}@media(max-width:600px){#ems-language-switcher{left:12px;bottom:12px}}';
        document.head.appendChild(style);

        const widget = document.createElement('div');
        widget.id = 'ems-language-switcher';
        widget.setAttribute('translate', 'no');
        widget.innerHTML = '<label for="ems-language-select">🌐</label> <select id="ems-language-select" aria-label="Language"><option value="en">English</option><option value="ur">اردو</option></select>';
        document.body.appendChild(widget);
        const select = document.getElementById('ems-language-select');
        select.addEventListener('change', function () { applyLanguage(select.value); });

        let language = 'en';
        try { language = localStorage.getItem('ems_language') || 'en'; } catch (error) {}
        applyLanguage(language);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install);
    else install();
}());
