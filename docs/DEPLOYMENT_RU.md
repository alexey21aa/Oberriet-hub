# Установка Oberriet Hub

## Хостинг и стоимость

Сравнение публичных предложений на 2026-10-01:

| Провайдер | Опубликованная стартовая цена | Основание |
|---|---:|---|
| [hosttech](https://www.hosttech.ch/webhosting/) | от CHF 6.90/месяц | Swiss datacenter, SSL, PHP/MariaDB, 50 GB; PHP memory_limit подтвердить до заказа |
| [Infomaniak](https://www.infomaniak.com/de/hosting/webhosting) | от CHF 10.91/месяц | Swiss hosting, резервные копии, 20 сайтов, 250 GB |
| [cyon](https://www.cyon.ch/hosting/webhosting) | от CHF 14.90/месяц | Swiss Basel hosting, поддержка, 30-дневное тестирование |

Предварительный выбор — базовый hosttech: хватает для лёгкого WordPress, самая низкая из этих трёх проверенных цен. Не покупался. Цена «от» зависит от срока/акции и условий расчёта; перед оплатой проверить корзину, НДС, продление, cron и стоимость домена. Ориентир 6.90 × 12 = CHF 82.80/год, домен отдельно. На странице сравнения hosttech встречалась другая стартовая цена; используем основной актуальный оффер, не обещаем фиксированную цену.

Постоянный бесплатный Swiss PHP/MariaDB production-хостинг с нужными гарантиями не подтверждён. Сам продукт, плагины и обязательный поиск бесплатны. Можно использовать уже оплаченный Swiss hosting без новых затрат.

## Новый сайт: без командной строки

1. Создать на выбранном хостинге сайт и отдельную MariaDB-базу с отдельным пользователем и сложным паролем. PHP 8.3 или более новая поддерживаемая совместимая версия; memory_limit не ниже 256 MB. Выбрать сервер в Швейцарии. Включить бесплатный SSL.
2. Загрузить **содержимое `wordpress/`** в document root. Остальные папки держать вне публичной области. Поставить 755 на директории, 644 на файлы; `wp-config.php` — 640, если позволяет сервер.
3. Открыть HTTPS URL и пройти штатный WordPress installer. Создать неочевидное имя администратора, собственный пароль и свой email. **Не использовать лабораторные admin/password.** Включить «не индексировать сайт» на время подготовки.
4. В `/wp-admin/plugins.php` активировать **Two Factor**, затем **OberHub Core**. В «Внешний вид → Темы» активировать **OberHub Theme**. Seed импортируется однократно при активации. В «Настройки → Постоянные ссылки» сохранить «Название записи». Название сайта — Oberriet Hub; timezone — Europe/Zurich.
5. В профиле администратора настроить TOTP через собственное приложение-аутентификатор, подтвердить код и сформировать recovery codes. Сохранить их отдельно от хостинга. Выйти и проверить новый вход с паролем и кодом. В Oberriet Hub должно быть **Admin 2FA: configured**. Повторить для каждого администратора.
6. В меню Oberriet Hub заполнить Operator, Address, Email, Hosting provider и Hosting country. Для этого нельзя использовать данные Gemeindeverwaltung: Hub независимый. Проверить Impressum и Privacy во всех языках.
7. Внести определения из `source/scripts/wp-config-security.php.example` до строки «stop editing» реального `wp-config.php`. Сгенерировать уникальные salts штатными средствами WordPress. Ключи необязательного ИИ не нужны для MVP. Не заменять рабочий `wp-config.php` примером.
8. Установить Apache-правила из `source/scripts/apache.htaccess` или эквивалент host-managed Nginx. Они закрывают directory listing, `.git`, `.env`, конфиг и HTTP. На reverse proxy сначала проверить настройку доверенного HTTPS у хостера, чтобы избежать циклического redirect. Проверить 301 HTTP→HTTPS и HTTPS `/de/`, `/en/`, `/ru/`, `/uk/`.
9. Настроить настоящий cron у хостера: каждые 5 минут запускать `wp cron event run --due-now --path=/absolute/document/root`. Если WP-CLI нет: `curl --fail --silent 'https://YOUR-DOMAIN/wp-cron.php?doing_wp_cron' >/dev/null` каждые 5 минут. **DISABLE_WP_CRON=true устанавливать только после работающего cron.** `oh_daily` выполняет маленькую очередь проверок источников и purge; посещаемость для этого не требуется.
10. Настроить нативный кеш хостинга только для анонимного публичного HTML. Исключить wp-admin, wp-login, wp-json, авторизованных пользователей и Set-Cookie. Даты поиска и отходов вычисляются в браузере из версии данных. После редактирования очистить кеш; при проблемах оставить кеш выключенным до проверки. Отдельный кеш-плагин не обязателен.
11. Сделать резервную копию и контрольное восстановление в закрытый staging по `BACKUP_RESTORE.md`. Настроить журналы самого хостинга: IP/request 7 суток, security 30, admin audit 180, агрегаты 13 месяцев. Политика приложения не очищает журналы веб-сервера.
12. Пройти сценарии из QA_REPORT и проверить данные/платежи/даты. В «Настройки → Чтение» снять noindex лишь после TOTP, реквизитов, HTTPS и проверки. HSTS включить в Hub после стабильного HTTPS. Не включать preload/includeSubDomains автоматически.

## Существующий WordPress

Сначала полный backup. Установить ZIP `oberhub-core.zip`, `oberhub-theme.zip`, `two-factor.zip` из поставки через штатные экраны плагинов/тем. Не заменять core и базу существующего сайта. Активировать плагины/тему, сохранить permalinks, выполнить пункты 5–12. Hub перехватывает главную страницу и URL четырёх языков — применять на выделенном сайте или после проверки staging.

## WP-CLI

После штатной установки базы/владельца:

```bash
wp plugin activate two-factor oberhub-core
wp theme activate oberhub-theme
wp option update blogname 'Oberriet Hub'
wp option update timezone_string Europe/Zurich
wp rewrite structure '/%postname%/' --hard
wp rewrite flush --hard
# Для обновления контента из проверенного JSON, после backup:
wp oberhub import /private/path/seed.json --overwrite
```

Перезапись совпадающих ID заменяет редакционные данные: делать только после export/backup. Без `--overwrite` существующие ID сохраняются.

## GitHub и WPVibe

В связанном GitHub были доступны ноль репозиториев. Коннектор не имеет операции создания репозитория; поэтому подготовлен локальный Git bundle. Создать пустой private repository `oberriet-hub` под нужным владельцем и предоставить его подключению. Импорт:

```bash
git clone /path/oberriet-hub.git.bundle oberriet-hub
cd oberriet-hub
git remote set-url origin git@github.com:YOUR-OWNER/oberriet-hub.git
git push -u origin main
```

Операции GitHub push можно выполнить через коннектор после выдачи доступа. Не публиковать секреты, рабочую базу, `.git`, журналы и backups в web-root.

В WPVibe список сайтов пуст. После появления WordPress HTTPS URL подключить сайт через авторизацию WPVibe и установить его бесплатный коннектор. WPVibe не создаёт хостинг или базу и не может активировать несуществующий сайт. Далее использовать его для read-only проверки, обслуживания и безопасных черновиков темы. Авторизация владельцем обязательна.

## Изменения для сборки 0.2.0

Активируйте Two Factor и OberHub Core 0.2.0, затем OberHub Theme 0.2.0. Большая база 364 услуг/2548 ответов ставится в возобновляемую очередь. Откройте Oberriet Hub в wp-admin и дождитесь стадии complete; можно закрыть страницу и продолжить позже. Во время установки публичная часть сообщает о setup (HTTP 503). На реальном сервере проверьте PHP memory_limit 512 MB и лимит времени final index phase; размер starter JSON 27.2 MB (25.9 MiB). Для HTTP-импорта целого файла post_max_size≥34MB/upload_max_filesize≥32MB; начальная установка из плагина не требует загрузки JSON.

Источник — seed внутри плагина; не импортируйте дополнительно knowledge-expansion поверх него без понимания overwrite. data/base-seed.json — восстановленный исходный маленький набор; data/seed.json — итоговый полный набор. Скрипт build-knowledge.py воспроизводит исследовательское расширение из base-seed и проверенных evidence, а окончательные офисы/FAQ/provenance включены в итоговый seed.

Публичные ответы работают без LLM API. Не добавляйте endpoint/key/model constants для бесплатной эксплуатации. Native браузерный адаптер не требует серверных ключей, не загружает модель и не гарантирует доступность на каждом устройстве.

Git bundle содержит восстановленную исходную историю и новые изменения. После появления разрешённого репозитория: git clone /private/path/oberriet-hub.git.bundle oberriet-hub; cd oberriet-hub; git remote rename origin recovered; git remote add origin <реальный GitHub URL>; git push -u origin main. Не размещайте bundle/.git/source/tests/backup в web-root.

Для первого импорта выделите PHP memory_limit=512M до активации плагина (через настройки хостинга). WP_MEMORY_LIMIT и WP_MAX_MEMORY_LIMIT можно установить в wp-config.php до строки stop editing. Это требование к PHP-процессу, а не гарантия достаточности любого дешёвого тарифа. Финальную фазу индекса измерьте на выбранном сервере и выставьте достаточный max_execution_time; тестовая среда WASM заметно медленнее native PHP.
