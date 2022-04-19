<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Курс программирования на С#
        $CharpCourse = new Course();
        $CharpCourse->setCode('0000');
        $CharpCourse->setTitle('Программирование на С#');
        $CharpCourse->setDescription(
            'Изучите C# и платформу .NET, включая .NET Core и начните практиковать объектно-ориентированное 
            программирование (ООП).'
        );

        // Урок 1
        $lesson = new Lesson();
        $lesson->setTitle('Введение в платформу .NET');
        $lesson->setContent(
            'В этом уроке будут освоены основные концепции, структура приложения под NET. и пошаговый гайд по загрузке.'
        );
        $lesson->setSerialNumber(1);
        $CharpCourse->addLesson($lesson);

        // Урок 2
        $lesson = new Lesson();
        $lesson->setTitle('Основы C#');
        $lesson->setContent(
            'Создание переменных, область видимости переменных, переполнения, алгебраические операции, 
            изменения и сравнения строк, массивы и часовой формат данных'
        );
        $lesson->setSerialNumber(2);
        $CharpCourse->addLesson($lesson);

        // Урок 3
        $lesson = new Lesson();
        $lesson->setTitle('Управление потоком исполнения (Control Flow)');
        $lesson->setContent(
            'Создаём переменные Литералы Область видимости переменных Переполнение 
        Алгебраические операции Экземплярные и статические методы Базовый API для работы со строками 
        Пустота строк Изменение строк StringBuilder Форматирование строк'
        );
        $lesson->setSerialNumber(3);
        $CharpCourse->addLesson($lesson);
        $manager->persist($CharpCourse);

        // Курс Java разработчика
        $JavaCourse = new Course();
        $JavaCourse->setCode('0001');
        $JavaCourse->setTitle('Java-разработчик');
        $JavaCourse->setDescription(
            'Java-разработчик пишет код, благодаря которому работает YouTube, Instagram, 
            Netflix, Facebook, сервисы Яндекс, Revolut. Java-разработчик — одна из самых сложных позиций 
            для рекрутера: на рынке дефицит специалистов. Поэтому на толковых начинающих 
            разработчиков всегда есть спрос.Профессия Java-разработчик занимает 1-е место в топ-50 
            востребованных профессий дистанционной работы в IT по данным исследования Министерства 
            экономического развития РФ. '
        );

        // Урок 1
        $lesson = new Lesson();
        $lesson->setTitle('Введение в профессию и синтаксис языка');
        $lesson->setContent(
            'На первом этапе вы установите среду разработки и настроите рабочее пространство. 
            Погрузитесь в основы языка Java, освоите базовые операции и функции и напишете свой первый код.
            В конце курса вы выполните первую курсовую работу — напишете книгу учета сотрудников. .'
        );
        $lesson->setSerialNumber(1);
        $JavaCourse->addLesson($lesson);

        // Урок 2
        $lesson = new Lesson();
        $lesson->setTitle('Работа с Java и его окружением');
        $lesson->setContent(
            'Перевернутые классы. Студенты отвечают на вопросы наставников и закрепляют знания.
            Углубите свои знания в языке Java, научитесь пользоваться библиотеками, коллекциями, 
            создавать generic-классы, работать со стримами. Получите опыт тестирования кода.
            В курсовой работе вы усовершенствуете книгу учета сотрудников, добавите дополнительный 
            функционал и научитесь выводить ее в браузер.'
        );
        $lesson->setSerialNumber(2);
        $JavaCourse->addLesson($lesson);

        // Урок 3
        $lesson = new Lesson();
        $lesson->setTitle('Работа с кодом');
        $lesson->setContent(
            'Узнаете, что такое HTTP/HTTPS. Познакомитесь с Postman и сможете использовать его при 
            тестировании web-приложения. Научитесь взаимодействовать с базами данных с помощью Spring Data.
             Освоите Hibernate и научитесь писать простые запросы к базе данных с помощью JPQL.
             Вы решите ряд реальных задач для сервиса, который оценивает надежность клиента перед тем,
              как выдать ему кредит (сервис кредитного скоринга).'
        );
        $lesson->setSerialNumber(3);
        $JavaCourse->addLesson($lesson);

        // Урок 4
        $lesson = new Lesson();
        $lesson->setTitle('Рефакторинг кода');
        $lesson->setContent(
            'Разберетесь, в чем основные различия между 8 и 11 версиями языка, какие инструменты
             появились в новой версии, на что нужно обращать внимание при работе в проектах на разных
              версиях. Поймете, что лучше использовать List. of вместо asList. Узнаете и оцените 
              на практике возможности IDE в работе с кодом. Поймете где и когда можно менять код.
               Разберетесь в практической пользе тестов.'
        );
        $lesson->setSerialNumber(4);
        $JavaCourse->addLesson($lesson);

        $manager->persist($JavaCourse);

        // Курс Frontend - разработчика
        $FrontCourse = new Course();
        $FrontCourse->setCode('0002');
        $FrontCourse->setTitle('Frontend-разработчик');
        $FrontCourse->setDescription(
            'В работе frontend-разработчика есть простор для творчества: вы будете визуализировать
             дизайнерские идеи и оживлять макеты. Сфера фронтенда очень динамична — каждый день 
             появляются новые устройства, браузеры, интерфейсы становятся сложнее.
              Если иметь достаточно любопытства к новому, войти в профессию будет легко!'
        );

        // Урок 1
        $lesson = new Lesson();
        $lesson->setTitle('Введение в профессию, вёрстка и дизайн');
        $lesson->setContent(
            'Что вас ждет в модуле: Погрузитесь в профессию фронтенд-разработчика и поймёте его
             роль в команде. Научитесь верстать одностраничные сайты и адаптировать их под различные устройства. 
             Получите базовые знания визуального дизайна и научитесь работать с редактором Figma.
             Проектный результат: По итогу модуля сделаете основу для интернет-магазина: свёрстанный функциональный 
             сайт с проработанным дизайном, адаптированный под разные устройства.'
        );
        $lesson->setSerialNumber(1);
        $FrontCourse->addLesson($lesson);

        // Урок 2
        $lesson = new Lesson();
        $lesson->setTitle('Основы JavaScript');
        $lesson->setContent(
            'Что вас ждет в модуле: Освоите базовый синтаксис языка JavaScript, а также узнаете 
            принципы объектно-ориентированного и функционального программирования и 
            поймёте, как они применяются в работе фронтенд-разработчика.
            Проектный результат: По результатам модуля сделаете свои первые шаги в использовании 
            JavaScript в браузере и дополните ваш интернет-магазин динамикой
             с помощью готовых функциональных кусков кода.'
        );
        $lesson->setSerialNumber(2);
        $FrontCourse->addLesson($lesson);

        // Урок 3
        $lesson = new Lesson();
        $lesson->setTitle('JavaScript в браузере');
        $lesson->setContent(
            'Что вас ждет в модуле: В рамках этого модуля вы научитесь полноценно работать с JavaScript
             в браузере для добавления динамичности вашему интерфейсу. Сможете читать чужой код, а также
              изучите принципы написания «чистого кода». Научитесь создавать графику с помощью кода в Canvas.
            Проектный результат: В рамках проекта по интернет-магазину вы сможете написать уже свои 
            функциональные решения: работу с корзиной, добавление товаров в избранное, каталог, 
            сортировка и фильтрация товаров.'
        );
        $lesson->setSerialNumber(3);
        $FrontCourse->addLesson($lesson);

        // Урок 4
        $lesson = new Lesson();
        $lesson->setTitle('Инструменты разработки');
        $lesson->setContent(
            'В этом модуле вы немного отойдете от темы функциональности проекта в 
            сторону его организации: сделаете полноценную сборку кода и подключите свой 
            проект к GIT. Вы узнаете об основных инструментых разработчика, которые важны для 
            удобства работы с проектом и скорости внесения изменений. Уходим от функциональности проекта 
            в сторону его организации: делаем полноценную сборку кода 
            и подключим к проекту GIT. Также в рамках модуля тестируем интернет-магазин на производительность  
            и адаптацию. Правильная организация проекта важна для удобства работы с ним и скорости внесения изменений.'
        );
        $lesson->setSerialNumber(4);
        $FrontCourse->addLesson($lesson);

        $manager->persist($FrontCourse);
        $manager->flush();
    }
}
