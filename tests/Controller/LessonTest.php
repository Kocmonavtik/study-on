<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use App\Tests\Authorization\Auth;
use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\DomCrawler\Crawler;

class LessonTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get(SerializerInterface::class);
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
    public function urlSuccess()
    {
        /*yield['/lessons/1'];
        yield['/lessons/1/edit'];*/
    }

    public function urlNotFound()
    {
        yield['/lessons/'];
        yield['lessons/new'];
    }
   /* public function urlInternalServerError()
    {
        yield['lessons/new'];
    }*/
    private function adminUser()
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        $data = [
            'username' => 'adminuser@gmail.com',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->auth($requestData);
        return $crawler;
    }
    /**
     * @group testPermissionsUserLesson
     */
    public function testPermissionsUserLesson(): void //каким образом проверить
    {
        $auth = new Auth();
        $auth->setSerializer($this->serializer);
        $data = [
            'username' => 'succUser@gmail.com',
            'password' => '123456'
        ];
        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->auth($requestData);

        $client = AbstractTest::getClient();
        $courseRepository = self::getEntityManager()->getRepository(Course::class);
        $courses = $courseRepository->findAll();
        foreach ($courses as $course) {
            foreach ($course->getLessons() as $lesson) {
                $client->request('GET', '/lessons/' . $lesson->getId());
                $this->assertResponseOk();

                $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
                $this->assertResponseRedirect();
                $client->followRedirect();
                $this->assertResponseOk();
                self::assertEquals('/courses/', $client->getRequest()->getPathInfo());

                $client->request('POST', '/lessons/' . $lesson->getId() . '/edit');
                $this->assertResponseRedirect();
                $client->followRedirect();
                $this->assertResponseOk();
                self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
            }
            $client->request('GET', '/lessons/new/' . $course->getId());
            $this->assertResponseRedirect();
            $client->followRedirect();
            $this->assertResponseOk();
            self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
        }
    }
    /**
     * @group testResponseLessonPages
     */
    public function testResponseLessonPages(): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $courseRepository = self::getEntityManager()->getRepository(Course::class);
        $courses = $courseRepository->findAll();
        foreach ($courses as $course) {
            foreach ($course->getLessons() as $lesson) {
                $client->request('GET', '/lessons/' . $lesson->getId());
                $this->assertResponseOk();

                $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
                $this->assertResponseOk();

                $client->request('POST', '/lessons/' . $lesson->getId() . '/edit');
                $this->assertResponseOk();
            }
            $client->request('GET', '/lessons/new/' . $course->getId());
            $this->assertResponseOk();
        }
    }

    /**
     * @group testCreateLesson
     */
    public function testCreateLesson(): void
    {
        $crawler = $this->adminUser();
        //Стартовая страница
        $client = AbstractTest::getClient();
        $url = '/courses/';

        //проверка стартовой страницы
        $crawler = $client->request('GET', $url);
        $this->assertResponseOk();

        $link = $crawler->filter('.open_course')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        //Создание курса и проверка на редирект со списком курсов
        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'lesson[title]' => 'Тестовый урок',
            'lesson[content]' => 'Урок, предназначенный для теста',
            'lesson[serial_number]' => 1000 - 7,
        ]);
        $course = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);
        $countLesson = count($course->getLessons());
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/courses/' . $course->getId()));
        $crawler = $client->followRedirect();

        //Проверка количества уроков
        $this->assertCount($countLesson + 1, $crawler->filter('.edit_lesson'));
    }

    /**
     * @group testDeleteLesson
     */
    public function testDeleteLesson(): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $url = '/courses/';

        //проверка стартовой страницы
        $crawler = $client->request('GET', $url);
        $this->assertResponseOk();

        //Переход на страницу курса
        $link = $crawler->filter('.open_course')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->filter('.edit_lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form();
        $course = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);
        $countLesson = count($course->getLessons());

        //Удаление курса
        $client->submitForm('lesson_delete');
        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        //Проверка удаления курса
        $this->assertCount($countLesson - 1, $crawler->filter('.edit_lesson'));
    }

    /**
     * @group testEditLesson
     */
    public function testEditLesson(): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $url = '/courses/';

        //проверка стартовой страницы
        $crawler = $client->request('GET', $url);
        $this->assertResponseOk();

        //Переход на страницу курса
        $link = $crawler->filter('.open_course')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        //Переход на страницу редактирования
        $link = $crawler->filter('.edit_lesson')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form();
        $course = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['code' => $form['lesson[course]']->getValue()]);
        $form['lesson[title]'] = 'Тестовый урок';
        $form['lesson[content]'] = 'Тест изменений урока';
        $form['lesson[serial_number]'] = 993 - 7;
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        //Проверка изменения курса
        $lessonTitle = $crawler->filter('.lesson_title')->text();
        $this->assertEquals('Тестовый урок', $lessonTitle);

        $lessonDescription = $crawler->filter('.lesson_content')->text();
        $this->assertEquals('Тест изменений урока', $lessonDescription);
    }

    /**
     * @group testUrlSuccessLesson
     * @dataProvider urlSuccess
     */
    public function testUrlSuccess($url): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $client->request('GET', $url);
        $this->assertResponseOk();
    }
    /**
     * @group testUrlNotFoundLesson
     * @dataProvider urlNotFound
     */
    public function testUrlNotFound($url): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }
   /* /**
     * @group testUrlInternalServerErrorLesson
     * @dataProvider urlInternalServerError
     */
   /* public function testUrlInternalServerError($url)
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $client->request('GET', $url);
        $this->assertResponseCode(404);
    }*/
    /**
     * @group testCreateBlankFieldsLesson
     */
    public function testCreateBlankFieldLesson(): void
    {
        $crawler = $this->adminUser();
        //Стартовая страница
        $client = AbstractTest::getClient();
        $url = '/courses/';

        //проверка стартовой страницы
        $crawler = $client->request('GET', $url);
        $this->assertResponseOk();

        $link = $crawler->filter('.open_course')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'lesson[title]' => '',
            'lesson[content]' => 'Урок, предназначенный для теста',
            'lesson[serial_number]' => 1000 - 7,
        ]);
        $client->submit($form);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('This value should not be blank.', $error->text());

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'lesson[title]' => 'Тестовый урок',
            'lesson[content]' => 'Урок, предназначенный для теста',
            'lesson[serial_number]' => ''
        ]);
        $client->submit($form);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('This value should not be blank.', $error->text());

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'lesson[title]' => 'Тестовый урок',
            'lesson[content]' => '',
            'lesson[serial_number]' => 1000 - 7,
        ]);
        $client->submit($form);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('This value should not be blank.', $error->text());
    }

    /**
     * @group testCreateInvalidLengthLesson
     */
    public function testCreateInvalidLengthLesson(): void
    {
        $crawler = $this->adminUser();
        //Стартовая страница
        $client = AbstractTest::getClient();
        $url = '/courses/';

        //проверка стартовой страницы
        $crawler = $client->request('GET', $url);
        $this->assertResponseOk();

        $link = $crawler->filter('.open_course')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'lesson[title]' => 'qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq
            qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq
            qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq
            qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq',
            'lesson[content]' => 'Урок, предназначенный для теста',
            'lesson[serial_number]' => 1000 - 7,
        ]);
        $client->submit($form);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('Название урока должно иметь не больше 255 символов', $error->text());

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'lesson[title]' => 'Тестовый урок',
            'lesson[content]' => 'Урок, предназначенный для теста',
            'lesson[serial_number]' => 10007,
        ]);
        $client->submit($form);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('Значение должно быть от 1 до 1000', $error->text());
    }
    /**
     * @group testDeleteLessonAfterDeleteCourse
     */
    public function testDeleteLessonAfterDeleteCourse(): void
    {
        $crawler = $this->adminUser();
        $client = self::getClient();

        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.open_course')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $lessons = $crawler->filter('.edit_lesson')->link();

        $client->submitForm('course_delete');
        $this->assertTrue($client->getResponse()->isRedirect('/courses/'));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        foreach ($lessons as $lesson) {
            $client->request('GET', $lesson);
            $this->assertResponseNotFound();
        }
    }
}
