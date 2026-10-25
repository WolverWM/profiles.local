        <div class="footer">
            <p>© <?php echo date('Y'); ?> УО "Белорусская государственная академия связи"</p>
            <p>Разработчик: Дюпина Диана Андреевна</p>
            <p>📧 <a href="mailto:www.fry.d@gmail.com">www.fry.d@gmail.com</a></p>
        </div>
    </div>

    <!-- ============================================================
         Модальное окно полной версии поста (задание 2.7)
         Заполняется через ajax/get_post.php при клике на карточку
         ============================================================ -->
    <div id="postModal" class="modal-overlay" hidden>
        <div class="modal post-modal">
            <button type="button" class="modal-close" id="postModalClose" aria-label="Закрыть">&times;</button>
            <div id="postModalBody"><!-- сюда подставляется HTML из ajax/get_post.php --></div>
        </div>
    </div>

    <?php if (!empty($showTutorial)): ?>
    <!-- ============================================================
         Туториал для новых пользователей: 4 шага (задание 2.7)
         Показывается один раз, пока tutorial_completed = 0
         ============================================================ -->
    <div id="tutorialModal" class="modal-overlay">
        <div class="modal tutorial-modal">
            <div class="tutorial-progress">
                <span class="tutorial-dot active" data-dot="1"></span>
                <span class="tutorial-dot" data-dot="2"></span>
                <span class="tutorial-dot" data-dot="3"></span>
                <span class="tutorial-dot" data-dot="4"></span>
            </div>
            <div class="tutorial-step active" data-step="1">
                <div class="tutorial-icon">👋</div>
                <h2>Добро пожаловать в Profiles!</h2>
                <p>Это локальная социальная сеть-портфолио для студентов и профессионалов.</p>
            </div>
            <div class="tutorial-step" data-step="2">
                <div class="tutorial-icon">✏️</div>
                <h2>Создавайте посты с текстом и изображениями</h2>
                <p>Форма публикации доступна в вашем профиле.</p>
            </div>
            <div class="tutorial-step" data-step="3">
                <div class="tutorial-icon">🎨</div>
                <h2>Настраивайте внешний вид профиля</h2>
                <p>В личном кабинете можно выбрать цветовую схему, размер сетки и другие параметры.</p>
            </div>
            <div class="tutorial-step" data-step="4">
                <div class="tutorial-icon">👤</div>
                <h2>Нажмите на аватар, чтобы перейти в свой профиль</h2>
                <p>Аватар в левом верхнем углу всегда ведёт в ваш профиль.</p>
            </div>
            <div class="tutorial-actions">
                <button type="button" id="tutorialSkip" class="btn-text">Пропустить</button>
                <button type="button" id="tutorialNext" class="btn">Далее</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="assets/js/validate.js"></script>
    <script src="assets/js/main.js"></script>
    <?php if (!empty($showTutorial)): ?>
    <script src="assets/js/tutorial.js"></script>
    <?php endif; ?>
</body>
</html>
