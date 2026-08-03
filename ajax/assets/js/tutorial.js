/**
 * ============================================================
 * tutorial.js — туториал для новых пользователей: 4 шага
 * с индикатором прогресса (задание 2.7)
 * ============================================================
 */
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('tutorialModal');
    if (!modal || !window.SHOW_TUTORIAL) return;

    const steps = [...modal.querySelectorAll('.tutorial-step')];
    const dots = [...modal.querySelectorAll('.tutorial-dot')];
    const nextBtn = document.getElementById('tutorialNext');
    const skipBtn = document.getElementById('tutorialSkip');
    let current = 1;
    const total = steps.length;

    function showStep(step) {
        steps.forEach(el => el.classList.toggle('active', parseInt(el.dataset.step, 10) === step));
        dots.forEach(el => el.classList.toggle('active', parseInt(el.dataset.dot, 10) === step));
        nextBtn.textContent = step === total ? 'Готово' : 'Далее';
    }

    function finishTutorial() {
        const formData = new FormData();
        formData.append('csrf_token', window.CSRF_TOKEN);
        fetch('ajax/tutorial.php', { method: 'POST', body: formData }).finally(function () {
            modal.hidden = true;
        });
    }

    nextBtn.addEventListener('click', function () {
        if (current >= total) {
            finishTutorial();
            return;
        }
        current++;
        showStep(current);
    });

    skipBtn.addEventListener('click', finishTutorial);

    showStep(current);
});
