/**
 * ============================================================
 * main.js — общая логика: лайки, поиск/фильтры, слайдер,
 * модальное окно поста, подписка, drag-and-drop сетки профиля
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    initLikeButtons();
    initSearchAndFilters();
    initSlider();
    initPostModal();
    initFollowButton();
    initGridDragAndDrop();
    initSeparateColorsToggle();
    initBlockDelete();
    initCustomStatusBadge();
    initColorPreview();
});

// ============================================================
// ЛАЙКИ (делегирование событий — карточки могут перерисовываться)
// ============================================================
function initLikeButtons() {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.like-btn');
        if (!btn) return;
        e.stopPropagation();

        const postId = btn.dataset.postId;
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('csrf_token', window.CSRF_TOKEN);

        fetch('ajax/like.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                // Обновляем все карточки этого поста на странице (лента + модалка)
                document.querySelectorAll('.like-btn[data-post-id="' + postId + '"]').forEach(function (el) {
                    el.querySelector('.like-count').textContent = data.likes;
                    const icon = el.querySelector('i');
                    if (data.liked) {
                        icon.classList.remove('far');
                        icon.classList.add('fas', 'liked');
                    } else {
                        icon.classList.remove('fas', 'liked');
                        icon.classList.add('far');
                    }
                });
            });
    });
}

// ============================================================
// AJAX-ПОИСК И ФИЛЬТРЫ (без перезагрузки страницы)
// ============================================================
function initSearchAndFilters() {
    const searchInput = document.getElementById('searchInput');
    const filterButtons = document.getElementById('filterButtons');
    const resultsBox = document.getElementById('feedResults');
    if (!resultsBox) return;

    let currentFilter = document.querySelector('.filter-btn.active');
    currentFilter = currentFilter ? currentFilter.dataset.filter : 'all';
    let debounceTimer = null;

    function runSearch() {
        const q = searchInput ? searchInput.value.trim() : '';
        const params = new URLSearchParams({ q: q, filter: currentFilter });
        fetch('ajax/search.php?' + params.toString())
            .then(r => r.text())
            .then(html => { resultsBox.innerHTML = html; });

        // Обновляем URL для удобства (без перезагрузки)
        const urlParams = new URLSearchParams();
        if (q) urlParams.set('search', q);
        if (currentFilter !== 'all') urlParams.set('filter', currentFilter);
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        history.replaceState(null, '', newUrl);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(runSearch, 300);
        });
    }

    if (filterButtons) {
        filterButtons.addEventListener('click', function (e) {
            const btn = e.target.closest('.filter-btn');
            if (!btn) return;
            filterButtons.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
            runSearch();
        });
    }
}

// ============================================================
// СЛАЙДЕР: автопрокрутка (задание 2.3)
// ============================================================
function initSlider() {
    const slider = document.getElementById('sliderContainer');
    if (!slider) return;

    // Шаг прокрутки — ширина ровно одной видимой "страницы" (5 карточек),
    // а не фиксированные пиксели: slider.clientWidth — это и есть ширина
    // видимой области контейнера (задание "показывать следующие 5 постов").
    function scrollToNextPage() {
        const maxScroll = slider.scrollWidth - slider.clientWidth;
        if (slider.scrollLeft >= maxScroll - 5) {
            // Дошли до конца (последние из 9 постов) — прокрутка зацикливается сначала
            slider.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            slider.scrollBy({ left: slider.clientWidth, behavior: 'smooth' });
        }
    }

    let autoScroll = setInterval(scrollToNextPage, 10000);

    // Останавливаем автопрокрутку при наведении, чтобы не мешать просмотру
    slider.addEventListener('mouseenter', () => clearInterval(autoScroll));
    slider.addEventListener('mouseleave', function () {
        autoScroll = setInterval(scrollToNextPage, 10000);
    });
}

// ============================================================
// МОДАЛЬНОЕ ОКНО ПОЛНОЙ ВЕРСИИ ПОСТА (задание 2.7)
// ============================================================
function initPostModal() {
    const modal = document.getElementById('postModal');
    const body = document.getElementById('postModalBody');
    const closeBtn = document.getElementById('postModalClose');
    if (!modal || !body) return;

    document.addEventListener('click', function (e) {
        // Клик по карточке поста (но не по кнопке лайка/ссылке автора/форме удаления)
        const card = e.target.closest('.post-card');
        if (!card) return;
        if (e.target.closest('.like-btn') || e.target.closest('a') || e.target.closest('form') || e.target.closest('button')) return;

        const postId = card.dataset.postId;
        if (!postId) return;

        fetch('ajax/get_post.php?id=' + postId)
            .then(r => r.text())
            .then(html => {
                body.innerHTML = html;
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            });
    });

    function closeModal() {
        modal.hidden = true;
        body.innerHTML = '';
        document.body.style.overflow = '';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });
}

// ============================================================
// ПОДПИСКА / ОТПИСКА (задание "Роли пользователей")
// ============================================================
function initFollowButton() {
    const btn = document.getElementById('followBtn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        const formData = new FormData();
        formData.append('target_id', btn.dataset.targetId);
        formData.append('csrf_token', window.CSRF_TOKEN);

        fetch('ajax/follow.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const icon = btn.querySelector('i');
                const label = btn.querySelector('span');
                if (data.following) {
                    btn.classList.add('btn-secondary');
                    icon.className = 'fas fa-user-check';
                    label.textContent = 'Вы подписаны';
                } else {
                    btn.classList.remove('btn-secondary');
                    icon.className = 'fas fa-user-plus';
                    label.textContent = 'Подписаться';
                }
            });
    });
}

// ============================================================
// DRAG-AND-DROP СЕТКИ ПРОФИЛЯ (задание 2.4, только владелец)
// ============================================================
function initGridDragAndDrop() {
    const grid = document.getElementById('profileGrid');
    if (!grid) return;

    let draggedEl = null;

    grid.addEventListener('dragstart', function (e) {
        const item = e.target.closest('[draggable="true"]');
        if (!item) return;
        draggedEl = item;
        setTimeout(() => item.classList.add('dragging'), 0);
    });

    grid.addEventListener('dragend', function () {
        if (draggedEl) draggedEl.classList.remove('dragging');
        draggedEl = null;
        saveGridOrder(grid);
    });

    grid.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!draggedEl) return;
        const afterEl = getDragAfterElement(grid, e.clientY);
        if (afterEl == null) {
            grid.appendChild(draggedEl);
        } else {
            grid.insertBefore(draggedEl, afterEl);
        }
    });
}

function getDragAfterElement(container, y) {
    const items = [...container.querySelectorAll('[draggable="true"]:not(.dragging)')];
    return items.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        }
        return closest;
    }, { offset: -Infinity, element: null }).element;
}

function saveGridOrder(grid) {
    const items = [...grid.querySelectorAll('[data-type][data-id]')].map(el => ({
        type: el.dataset.type,
        id: parseInt(el.dataset.id, 10),
    }));

    const formData = new FormData();
    formData.append('order', JSON.stringify(items));
    formData.append('csrf_token', window.CSRF_TOKEN);
    fetch('ajax/reorder.php', { method: 'POST', body: formData });
}

// ============================================================
// УДАЛЕНИЕ ТЕКСТОВОГО БЛОКА В СЕТКЕ ПРОФИЛЯ (только владелец)
// ============================================================
function initBlockDelete() {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.block-delete-btn');
        if (!btn) return;
        e.stopPropagation(); // не даём клику "провалиться" на карточку

        if (!confirm('Удалить эту заметку?')) return;

        const formData = new FormData();
        formData.append('id', btn.dataset.blockId);
        formData.append('csrf_token', window.CSRF_TOKEN);

        fetch('ajax/delete_block.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Убираем карточку заметки со страницы без перезагрузки
                    btn.closest('.text-block-card').remove();
                }
            });
    });
}

// ============================================================
// ПЕРЕКЛЮЧАТЕЛЬ "РАЗДЕЛЬНАЯ НАСТРОЙКА ФОНА И КНОПОК" (dashboard.php)
// ============================================================
function initSeparateColorsToggle() {
    const checkbox = document.getElementById('separateColorsCheckbox');
    const bgGroup = document.getElementById('bgColorGroup');
    const buttonGroup = document.getElementById('buttonColorGroup');
    const buttonTextGroup = document.getElementById('buttonTextColorGroup');
    const buttonTextHint = document.getElementById('buttonTextColorHint');
    if (!checkbox || !bgGroup || !buttonGroup) return;
    checkbox.addEventListener('change', function () {
        // "block" (не "flex"!) — чтобы каждая .theme-group сама осталась
        // колонкой (подпись сверху, кружок цвета снизу); при этом как
        // flex-элемент внутри .theme-colors-row она всё равно встанет
        // в общий горизонтальный ряд вместе с остальными цветами.
        const display = checkbox.checked ? 'block' : 'none';
        bgGroup.style.display = display;
        buttonGroup.style.display = display;
        // Цвет текста на кнопках показывается только вместе с раздельной
        // настройкой — иначе в форме было бы два разных поля цвета текста
        // сразу (общий "Цвет шрифта" и "Цвет текста на кнопках"), хотя при
        // выключенной галочке кнопки и так берут цвет текста темы.
        if (buttonTextGroup) buttonTextGroup.style.display = display;
        if (buttonTextHint) buttonTextHint.style.display = checkbox.checked ? 'none' : 'block';
    });
}

// ============================================================
// ПОЛЕ ВВОДА КАСТОМНОГО СТАТУСА (dashboard.php) — показывается,
// когда в списке "Профессиональный статус" выбрано "Другое"
// ============================================================
function initCustomStatusBadge() {
    const select = document.getElementById('statusBadgeSelect');
    const customInput = document.getElementById('statusBadgeCustom');
    if (!select || !customInput) return;
    select.addEventListener('change', function () {
        customInput.style.display = select.value === 'custom' ? 'block' : 'none';
    });
}

// ============================================================
// ЖИВОЕ ПРЕВЬЮ ЦВЕТОВОЙ СХЕМЫ (dashboard.php, задание "Live preview")
// ============================================================
// JS-версии darkenColor()/lightenColor() из includes/functions.php — нужны,
// чтобы превью правильно показывало АВТОМАТИЧЕСКИЙ фон/цвет кнопок (когда
// галочка "раздельная настройка" выключена), а не только выбранные вручную.
function darkenColorJs(hex, percent) {
    const num = parseInt(hex.slice(1), 16);
    const r = Math.max(0, (num >> 16) - ((num >> 16) * percent / 100));
    const g = Math.max(0, ((num >> 8) & 0xff) - (((num >> 8) & 0xff) * percent / 100));
    const b = Math.max(0, (num & 0xff) - ((num & 0xff) * percent / 100));
    return '#' + [r, g, b].map(v => Math.round(v).toString(16).padStart(2, '0')).join('');
}
function lightenColorJs(hex, percent) {
    const num = parseInt(hex.slice(1), 16);
    const r = Math.min(255, (num >> 16) + ((255 - (num >> 16)) * percent / 100));
    const g = Math.min(255, ((num >> 8) & 0xff) + ((255 - ((num >> 8) & 0xff)) * percent / 100));
    const b = Math.min(255, (num & 0xff) + ((255 - (num & 0xff)) * percent / 100));
    return '#' + [r, g, b].map(v => Math.round(v).toString(16).padStart(2, '0')).join('');
}

function initColorPreview() {
    const form = document.getElementById('colorForm');
    const preview = document.getElementById('colorPreview');
    const previewBtn = document.getElementById('colorPreviewBtn');
    if (!form || !preview || !previewBtn) return;

    const primaryInput = form.querySelector('[name="primary_color"]');
    const textInput = form.querySelector('[name="text_color"]');
    const bgInput = form.querySelector('[name="bg_color"]');
    const buttonInput = form.querySelector('[name="button_color"]');
    const buttonTextInput = form.querySelector('[name="button_text_color"]');
    const separateCheckbox = document.getElementById('separateColorsCheckbox');

    function updatePreview() {
        const primary = primaryInput.value;
        const text = textInput.value;
        // Если галочка выключена, реальная страница вычисляет фон/кнопки
        // автоматически из основного цвета — превью повторяет ту же логику,
        // а не берёт значения скрытых полей (они могут быть устаревшими).
        // Цвет текста на кнопках при выключенной галочке точно так же
        // совпадает с общим цветом шрифта, а не с (тоже скрытым) buttonTextInput.
        const bg = separateCheckbox.checked ? bgInput.value : darkenColorJs(primary, 15);
        const button = separateCheckbox.checked ? buttonInput.value : lightenColorJs(primary, 25);
        const buttonText = separateCheckbox.checked ? buttonTextInput.value : text;

        preview.style.background = 'linear-gradient(135deg, ' + primary + ', ' + bg + ')';
        preview.style.color = text;
        previewBtn.style.background = button;
        previewBtn.style.color = buttonText;
    }

    [primaryInput, textInput, bgInput, buttonInput, buttonTextInput].forEach(function (input) {
        input.addEventListener('input', updatePreview);
    });
    separateCheckbox.addEventListener('change', updatePreview);
}
