/* =====================================================================
   Портал «Хвост и Лапы» — клиентские скрипты
   frontend/assets/js/app.js
   Слайдер, бургер-меню, клиентская валидация, автоскрытие сообщений.
   Работает без интернета и сторонних библиотек.
   ===================================================================== */

(function () {
    'use strict';

    /* =================================================================
       Слайдер: автосмена каждые 3 с, кнопки вперёд/назад, точки
       ================================================================= */
    var SLIDER_INTERVAL = 3000;

    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        var track   = slider.querySelector('[data-slider-track]');
        var slides  = slider.querySelectorAll('[data-slide]');
        var prevBtn = slider.querySelector('[data-slider-prev]');
        var nextBtn = slider.querySelector('[data-slider-next]');
        var dots    = slider.querySelectorAll('[data-slider-dot]');

        if (!track || slides.length === 0) {
            return;
        }

        var index = 0;
        var timer = null;

        function show(next) {
            index = (next + slides.length) % slides.length;
            track.style.transform = 'translateX(-' + (index * 100) + '%)';

            slides.forEach(function (slide, i) {
                slide.classList.toggle('is-active', i === index);
                slide.setAttribute('aria-hidden', i === index ? 'false' : 'true');
            });

            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === index);
                dot.setAttribute('aria-current', i === index ? 'true' : 'false');
            });
        }

        function start() {
            stop();
            timer = setInterval(function () {
                show(index + 1);
            }, SLIDER_INTERVAL);
        }

        function stop() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                show(index - 1);
                start();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                show(index + 1);
                start();
            });
        }

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () {
                show(i);
                start();
            });
        });

        // Пауза при наведении — удобно, когда пользователь читает текст.
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);

        // Свайп на мобильных устройствах.
        var startX = null;

        slider.addEventListener('touchstart', function (event) {
            startX = event.touches[0].clientX;
            stop();
        }, { passive: true });

        slider.addEventListener('touchend', function (event) {
            if (startX === null) {
                return;
            }
            var diff = event.changedTouches[0].clientX - startX;

            if (Math.abs(diff) > 45) {
                show(diff > 0 ? index - 1 : index + 1);
            }

            startX = null;
            start();
        });

        show(0);
        start();
    });

    /* =================================================================
       Бургер-меню
       ================================================================= */
    var burger = document.querySelector('[data-burger]');
    var nav    = document.querySelector('[data-nav]');

    if (burger && nav) {
        burger.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* =================================================================
       Клиентская валидация форм
       Показывает сообщение под полем до отправки на сервер.
       ================================================================= */
    var MESSAGES = {
        valueMissing: 'Заполните поле.',
        tooShort: 'Слишком короткое значение.',
        typeMismatch: 'Значение имеет неверный формат.',
        patternMismatch: 'Значение не соответствует требуемому формату.'
    };

    function messageFor(input, error) {
        if (error === 'valueMissing') {
            return MESSAGES.valueMissing;
        }
        if (error === 'tooShort') {
            return MESSAGES.tooShort + ' Минимум ' + (input.minLength || 1) + ' символов.';
        }
        return MESSAGES[error] || 'Проверьте значение поля.';
    }

    function showError(input, text) {
        var field = input.closest('.field');

        if (!field) {
            return;
        }

        field.classList.add('field--invalid');

        var box = field.querySelector('.field__error');

        if (!box) {
            box = document.createElement('p');
            box.className = 'field__error';
            field.appendChild(box);
        }

        box.textContent = text;
    }

    function clearError(input) {
        var field = input.closest('.field');

        if (!field || !field.classList.contains('field--invalid')) {
            return;
        }

        field.classList.remove('field--invalid');

        var box = field.querySelector('.field__error');

        if (box) {
            box.remove();
        }
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.setAttribute('novalidate', 'novalidate');

        form.addEventListener('submit', function (event) {
            var invalid = false;

            form.querySelectorAll('input, textarea, select').forEach(function (input) {
                if (!input.willValidate || input.disabled) {
                    return;
                }

                var error = input.validity.valueMissing ? 'valueMissing'
                    : input.validity.tooShort ? 'tooShort'
                    : input.validity.typeMismatch ? 'typeMismatch'
                    : input.validity.patternMismatch ? 'patternMismatch'
                    : '';

                if (error) {
                    showError(input, messageFor(input, error));
                    invalid = true;
                } else {
                    clearError(input);
                }
            });

            if (invalid) {
                event.preventDefault();

                var first = form.querySelector('.field--invalid .field__input');
                if (first) {
                    first.focus();
                }
            }
        });

        // Убираем ошибку, как только пользователь начал исправлять поле.
        form.addEventListener('input', function (event) {
            if (event.target.matches('.field__input')) {
                clearError(event.target);
            }
        });
    });

    /* =================================================================
       Flash-сообщения: закрытие вручную и автоскрытие
       ================================================================= */
    document.querySelectorAll('[data-toast]').forEach(function (toast) {
        var close = toast.querySelector('.alert__close');

        function hide() {
            toast.style.transition = 'opacity .3s ease';
            toast.style.opacity = '0';
            setTimeout(function () {
                toast.remove();
            }, 300);
        }

        if (close) {
            close.addEventListener('click', hide);
        }

        setTimeout(hide, 6000);
    });
})();