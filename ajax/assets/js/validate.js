/**
 * ============================================================
 * validate.js — клиентская (JavaScript) валидация форм
 * Требование методички: "валидация форм" средствами JS, работа с DOM
 * и обработка событий. Серверная проверка (PHP) остаётся основной
 * защитой — эта проверка лишь не даёт отправить явно неверные данные
 * и сразу показывает пользователю, что не так, без похода на сервер.
 * ============================================================
 *
 * Как подключить проверку к форме:
 *   <form data-validate>
 *       <input data-rule="required|username">
 *       <input data-rule="required|email">
 *       <input data-rule="required|minlength:6">
 *       <input data-rule="required|match:password">
 *   </form>
 *
 * Доступные правила (можно комбинировать через "|"):
 *   required        — поле не должно быть пустым (с учётом пробелов)
 *   username         — 3-50 символов, латиница/цифры/_/-
 *   email            — простая проверка формата email
 *   minlength:N      — не короче N символов
 *   match:имя_поля   — должно совпадать со значением другого поля формы
 */

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-validate]').forEach(initFormValidation);
});

function initFormValidation(form) {
    form.addEventListener('submit', function (event) {
        let isValid = true;

        form.querySelectorAll('[data-rule]').forEach(function (field) {
            clearFieldError(field);
            const rules = field.dataset.rule.split('|');
            for (const rule of rules) {
                const error = checkRule(field, rule, form);
                if (error) {
                    showFieldError(field, error);
                    isValid = false;
                    break;
                }
            }
        });

        if (!isValid) {
            event.preventDefault();
            const firstInvalid = form.querySelector('.field-invalid');
            if (firstInvalid) firstInvalid.focus();
        }
    });

    // Убираем сообщение об ошибке, как только пользователь начал исправлять поле
    form.querySelectorAll('[data-rule]').forEach(function (field) {
        field.addEventListener('input', function () { clearFieldError(field); });
    });
}

function checkRule(field, rule, form) {
    const value = field.value.trim();

    if (rule === 'required' && value === '') {
        return 'Обязательное поле';
    }

    if (rule === 'username' && value !== '') {
        if (value.length < 3 || value.length > 50) return 'Имя пользователя: от 3 до 50 символов';
        if (!/^[A-Za-z0-9_-]+$/.test(value)) return 'Только латиница, цифры, _ и -';
    }

    if (rule === 'email' && value !== '') {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Некорректный email';
    }

    if (rule.startsWith('minlength:')) {
        const min = parseInt(rule.split(':')[1], 10);
        if (value !== '' && value.length < min) return 'Не короче ' + min + ' символов';
    }

    if (rule.startsWith('match:')) {
        const otherName = rule.split(':')[1];
        const otherField = form.querySelector('[name="' + otherName + '"]');
        if (otherField && value !== otherField.value.trim()) return 'Значения не совпадают';
    }

    return null;
}

function showFieldError(field, message) {
    field.classList.add('field-invalid');
    let errorEl = field.nextElementSibling;
    if (!errorEl || !errorEl.classList.contains('field-error')) {
        errorEl = document.createElement('span');
        errorEl.className = 'field-error';
        field.insertAdjacentElement('afterend', errorEl);
    }
    errorEl.textContent = message;
}

function clearFieldError(field) {
    field.classList.remove('field-invalid');
    const next = field.nextElementSibling;
    if (next && next.classList.contains('field-error')) {
        next.remove();
    }
}
