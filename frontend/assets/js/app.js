/* =====================================================================
   Портал «Хвост и Лапы» — клиентские скрипты
   frontend/assets/js/app.js
   Слайдер, бургер-меню, клиентская валидация, выезжающие уведомления,
   подтверждение действий, анимация смены статуса в панели администратора.
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
       Выезжающие уведомления
       Один стек на страницу: и flash-сообщения, и ошибки вне формы,
       и ответы на fetch-запросы выглядят одинаково.
       ================================================================= */
    var TOAST_LIFETIME = 6000;
    var stack = document.querySelector('[data-toasts]');

    function toastIcon(type) {
        return type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    }

    function hideToast(node) {
        node.classList.add('is-leaving');
        setTimeout(function () {
            node.remove();
        }, 350);
    }

    function bindToast(node) {
        // Имя класса — toast-item, а не toast: у Bootstrap есть компонент
        // .toast с правилом .toast:not(.showing) { display: none },
        // из-за которого уведомление пропадало бы совсем.
        node.classList.add('toast-item');

        var close = node.querySelector('.alert__close');

        if (close) {
            close.addEventListener('click', function () {
                hideToast(node);
            });
        }

        setTimeout(function () {
            hideToast(node);
        }, TOAST_LIFETIME);
    }

    /** Показать уведомление из скрипта. */
    function toast(type, text) {
        if (!stack) {
            return;
        }

        var icon  = document.createElement('i');
        var body  = document.createElement('span');
        var close = document.createElement('button');

        // Текст приходит с сервера — вставляется только через textContent.
        icon.className  = 'bi ' + toastIcon(type);
        body.textContent = text;

        close.type = 'button';
        close.className = 'alert__close';
        close.setAttribute('aria-label', 'Закрыть');
        close.innerHTML = '&times;';

        var node = document.createElement('div');
        node.className = 'alert alert--' + type;
        node.appendChild(icon);
        node.appendChild(body);
        node.appendChild(close);

        stack.appendChild(node);
        bindToast(node);
    }

    // Готовые блоки из разметки переносим в тот же стек.
    document.querySelectorAll('[data-toast]').forEach(function (node) {
        if (stack && node.parentNode !== stack) {
            stack.appendChild(node);
        }

        bindToast(node);
    });

    /* =================================================================
       Появление блоков при прокрутке
       ================================================================= */
    var reveals = document.querySelectorAll('[data-reveal]');

    if (reveals.length > 0) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.05, rootMargin: '0px 0px -30px 0px' });

            reveals.forEach(function (node) {
                observer.observe(node);
            });
        } else {
            // Без поддержки IntersectionObserver показываем всё сразу.
            reveals.forEach(function (node) {
                node.classList.add('is-visible');
            });
        }
    }

    /* =================================================================
       Подтверждение действия
       Первое нажатие показывает вопрос, второе — отправляет форму.
       Без JavaScript форма отправляется сразу обычным POST.
       ================================================================= */
    document.querySelectorAll('[data-confirm]').forEach(function (box) {
        var form   = box.querySelector('form');
        var open   = box.querySelector('[data-confirm-open]');
        var ask    = box.querySelector('[data-confirm-ask]');
        var accept = box.querySelector('[data-confirm-accept]');
        var back   = box.querySelector('[data-confirm-cancel]');

        if (!form || !open || !ask || !accept) {
            return;
        }

        function showAsk(show) {
            open.hidden = show;
            ask.hidden  = !show;

            (show ? accept : open).focus();
        }

        form.addEventListener('submit', function (event) {
            if (event.submitter === accept) {
                return;
            }

            event.preventDefault();
            showAsk(true);
        });

        if (back) {
            back.addEventListener('click', function () {
                showAsk(false);
            });
        }
    });

    /* =================================================================
       Клиентская валидация форм
       Показывает сообщение под полем до отправки на сервер.
       ================================================================= */
    var MESSAGES = {
        valueMissing: 'Заполните поле.',
        tooShort: 'Слишком короткое значение.',
        tooLong: 'Слишком длинное значение.',
        typeMismatch: 'Значение имеет неверный формат.',
        patternMismatch: 'Значение не соответствует требуемому формату.',
        rangeUnderflow: 'Значение меньше минимального.',
        rangeOverflow: 'Значение больше максимального.',
        stepMismatch: 'Значение не попадает в сетку интервалов.'
    };

    function messageFor(input, error) {
        // У полей date и time браузер знает границы сам — подставляем их.
        if ((error === 'rangeUnderflow' || error === 'rangeOverflow') && input.min && input.max) {
            return 'Выберите значение от ' + input.min + ' до ' + input.max + '.';
        }

        if (error === 'tooShort') {
            return MESSAGES.tooShort + ' Минимум ' + (input.minLength || 1) + ' символов.';
        }

        return MESSAGES[error] || 'Проверьте значение поля.';
    }

    /** Первая ошибка проверки браузера — в том же порядке, что и в Validator. */
    function errorOf(input) {
        var validity = input.validity;
        var errors = [
            'valueMissing', 'tooShort', 'tooLong', 'typeMismatch',
            'patternMismatch', 'rangeUnderflow', 'rangeOverflow', 'stepMismatch'
        ];

        for (var i = 0; i < errors.length; i++) {
            if (validity[errors[i]]) {
                return errors[i];
            }
        }

        return '';
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

                var error = errorOf(input);

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
       Смена статуса заявки в панели администратора
       Форма отправляется через fetch: страница не перезагружается,
       бейдж анимируется, счётчики и форма переходов обновляются на месте.
       Без JavaScript форма уходит обычным POST с перезагрузкой.
       ================================================================= */
    function updateCounts(counts) {
        document.querySelectorAll('[data-stats] .stat').forEach(function (stat) {
            var key = stat.getAttribute('data-stat');

            if (!counts || !Object.prototype.hasOwnProperty.call(counts, key)) {
                return;
            }

            var value = stat.querySelector('.stat__value');

            if (value) {
                value.textContent = counts[key];
                pulse(value);
            }
        });
    }

    function updateResultTotal(total) {
        var node = document.querySelector('[data-result-total]');

        if (node && typeof total === 'number') {
            node.textContent = total;
            pulse(node);
        }
    }

    function pulse(node) {
        node.classList.remove('is-pulsed');
        void node.offsetWidth;                 // чтение layout перезапускает анимацию
        node.classList.add('is-pulsed');
    }

    /** Пересобрать ячейку действий под новый набор переходов. */
    function buildActions(source, transitions) {
        var cell = source.closest('[data-status-actions]');
        var form = source;

        if (!cell) {
            return;
        }

        cell.textContent = '';

        if (!transitions || transitions.length === 0) {
            var dash = document.createElement('span');
            dash.className = 'muted';
            dash.textContent = '—';
            cell.appendChild(dash);
            return;
        }

        var id = form.querySelector('[name="application_id"]').value;

        var fresh = document.createElement('form');
        fresh.method = 'post';
        fresh.action = form.action;
        fresh.className = 'inline-form';
        fresh.setAttribute('data-status-form', '');

        var hiddenId = document.createElement('input');
        hiddenId.type = 'hidden';
        hiddenId.name = 'application_id';
        hiddenId.value = id;

        var hiddenBack = document.createElement('input');
        hiddenBack.type = 'hidden';
        hiddenBack.name = 'return';
        hiddenBack.value = form.querySelector('[name="return"]').value;

        var select = document.createElement('select');
        select.className = 'field__input field__input--sm';
        select.name = 'status';
        select.setAttribute('aria-label', 'Новый статус заявки №' + id);

        transitions.forEach(function (status) {
            var option = document.createElement('option');
            option.value = status;
            option.textContent = status;
            select.appendChild(option);
        });

        var button = document.createElement('button');
        button.type = 'submit';
        button.className = 'btn btn--sm btn--primary';
        button.textContent = 'OK';

        fresh.appendChild(hiddenId);
        fresh.appendChild(hiddenBack);
        fresh.appendChild(select);
        fresh.appendChild(button);
        cell.appendChild(fresh);
    }

    function applyStatus(form, data) {
        var row = form.closest('[data-application-row]');

        if (!row) {
            return;
        }

        var badge = row.querySelector('[data-status-badge]');

        if (badge) {
            badge.className = 'badge ' + data.badge;
            badge.textContent = data.status;
            pulse(badge);
        }

        buildActions(form, data.transitions);

        updateCounts(data.counts);
        updateResultTotal(data.total);

        // При активном фильтре по статусу заявка изменила статус и
        // больше не принадлежит выборке — уезжает из таблицы.
        var wrap = document.querySelector('[data-status-filter]');
        var filter = wrap ? wrap.getAttribute('data-status-filter') : '';

        if (filter && filter !== data.status) {
            row.classList.add('is-leaving');
            setTimeout(function () {
                row.remove();
            }, 400);
        }
    }

    document.querySelectorAll('form[data-status-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var button = form.querySelector('button[type="submit"]');

            if (button) {
                button.disabled = true;
                button.classList.add('is-busy');
            }

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data && data.ok) {
                        applyStatus(form, data);
                        toast('success', data.message);
                    } else {
                        toast('danger', (data && data.message) || 'Не удалось изменить статус.');
                    }
                })
                .catch(function () {
                    toast('danger', 'Сервер недоступен. Обновите страницу и попробуйте снова.');
                })
                .then(function () {
                    if (button) {
                        button.disabled = false;
                        button.classList.remove('is-busy');
                    }
                });
        });
    });
})();