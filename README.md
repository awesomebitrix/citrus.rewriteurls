
# Модуль-полуфарбрикат для реврайтов и динамической замены урл в страницах сайта (для SEO)

- список для реврайтов и замены урл пока разные файлы - данные в виде ассоциативных массивов
- параметры урла перед проверкой отбрасываются (опционально)
- настроить auto_prepend_file = .prepend.php (.htaccess или php.ini) - нужно для виртуальных разделов/страниц
- используется обработчик OnFileRewrite для статических разделов/страниц
- привязка настроек по домену (домен в названии файла)
- совместим с php 5.3 и windows-1251 кодировкой

Примеры настроек - см. папку /install/data/ - на демке для "Корпоративный сайт услуг" (bitrix.sitecorporate)
