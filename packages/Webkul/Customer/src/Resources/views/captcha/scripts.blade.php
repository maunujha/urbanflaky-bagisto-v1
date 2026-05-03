<script>
    window.onload = function() {
        let script = document.createElement('script');

        script.src = '{{ $clientEndPoint }}?render={{ $siteKey }}';

        script.onload = function() {
            grecaptcha.ready(function() {
                const forms = document.querySelectorAll('form');

                forms.forEach(function(form) {
                    const tokenField = form.querySelector('#recaptcha-token');

                    if (tokenField) {
                        // Pre-fill token on page load
                        grecaptcha.execute('{{ $siteKey }}', { action: 'submit' })
                            .then(function(token) {
                                tokenField.value = token;
                            });

                        form.addEventListener('submit', function(e) {
                            if (tokenField.value) {
                                return true;
                            }

                            e.preventDefault();

                            grecaptcha.execute('{{ $siteKey }}', { action: 'submit' })
                                .then(function(token) {
                                    tokenField.value = token;
                                    form.submit();
                                });
                        });
                    }
                });
            });
        };

        document.body.appendChild(script);
    };
</script>
