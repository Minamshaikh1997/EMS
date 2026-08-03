(function () {
    'use strict';

    fetch('company_branding.php', { credentials: 'same-origin', cache: 'no-store' })
        .then(function (response) {
            if (!response.ok) throw new Error('Branding unavailable');
            return response.json();
        })
        .then(function (branding) {
            var name = String(branding.company_name || '').trim();
            if (name) {
                var heading = document.getElementById('companyName');
                var footer = document.getElementById('footerCompanyName');
                if (heading) heading.textContent = name;
                if (footer) footer.textContent = name;
                document.title = name + ' - Login';
            }

            if (branding.logo_url) {
                var logoBox = document.getElementById('companyLogoBox');
                if (logoBox) {
                    var image = document.createElement('img');
                    image.src = branding.logo_url;
                    image.alt = name ? name + ' logo' : 'Company logo';
                    logoBox.replaceChildren(image);
                    logoBox.classList.add('has-company-logo');
                }
            }
        })
        .catch(function () {
            // Keep the built-in EMS branding when company details are unavailable.
        });
}());
