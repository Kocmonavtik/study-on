<?php

declare(strict_types=1);

namespace App\Tests\Controller;

/*use Symfony\Component\Panther\PantherTestCase;*/

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use App\Tests\Authorization\Auth;
use Doctrine\Persistence\ObjectManager;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\DomCrawler\Crawler;

class TestCourseTest extends AbstractTest
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
        yield ['/courses/'];
        yield ['/courses/new'];
    }

    public function urlNotFound()
    {
        yield ['/courses/0'];
        yield ['/abstractUrl/'];
    }

    public function urlInternalServerError()
    {
        yield ['courses/lol'];
    }

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
     * @group testPermissionsUserCourse
     */
    public function testPermissionsUserCourse(): void //каким образом проверить
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
            $client->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();

            $client->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();

            $client->request('GET', '/lessons/new/' . $course->getId());
            $this->assertResponseOk();

            $client->request('POST', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();

            $client->request('POST', '/lessons/new/' . $course->getId());
            $this->assertResponseOk();
        }
        $client->request('GET', '/courses/new');
        $this->assertResponseOk();

        $client->request('POST', '/courses/new');
        $this->assertResponseOk();
    }
    /**
     * @group testResponsePages
     */
    public function testResponsePages(): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $courseRepository = self::getEntityManager()->getRepository(Course::class);
        $courses = $courseRepository->findAll();
        foreach ($courses as $course) {
            $client->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();

            $client->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();

            $client->request('GET', '/lessons/new/' . $course->getId());
            $this->assertResponseOk();

            $client->request('POST', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();

            $client->request('POST', '/lessons/new/' . $course->getId());
            $this->assertResponseOk();
        }
        $client->request('GET', '/courses/new');
        $this->assertResponseOk();

        $client->request('POST', '/courses/new');
        $this->assertResponseOk();
    }
    /**
     * @group testCreateCourse
     */
    public function testCreateCourse(): void
    {
        $crawler = $this->adminUser();
        //Стартовая страница
        $client = AbstractTest::getClient();
        $url = '/courses/';

        //проверка стартовой страницы
        $crawler = $client->request('GET', $url);
        $this->assertResponseOk();

        //Переход на страницу создания курса
        $link = $crawler->filter('.create_course')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        //Создание курса и проверка на редирект со списком курсов
        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'course[code]' => 'FFFF',
            'course[title]' => 'Тестовый курс',
            'course[description]' => 'Данный курс предназначен для тестирования',
        ]);
        $courseRepository = self::getEntityManager()->getRepository(Course::class);
        $countCourses = count($courseRepository->findAll());
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/courses/'));

        //Проверка количества курсов
        $crawler = $client->followRedirect();
        $this->assertCount($countCourses + 1, $crawler->filter('.open_course'));
    }

    /**
     * @group testDeleteCourse
     */
    public function testDeleteCourse(): void
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

        $courseRepository = self::getEntityManager()->getRepository(Course::class);
        $countCourses = count($courseRepository->findAll());

        //Удаление курса
        $client->submitForm('course_delete');
        $this->assertTrue($client->getResponse()->isRedirect('/courses/'));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        //Проверка удаления курса
        $this->assertCount($countCourses - 1, $crawler->filter('.open_course'));
    }

    /**
     * @group testEditCourse
     */
    public function testEditCourse(): void
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
        $link = $crawler->filter('.course_edit')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        //Изменение курса
        $button = $crawler->selectButton('Сохранить');
        $form = $button->form();
        $course = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['code' => $form['course[code]']->getValue()]);
        $form['course[code]'] = 'TESTCOURSE';
        $form['course[title]'] = 'Измененный курс';
        $form['course[description]'] = 'Данный курс необходим для тестирования';
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/courses/' . $course->getId()));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        //Проверка изменения курса
        $courseTitle = $crawler->filter('.title_course')->text();
        $this->assertEquals('Измененный курс', $courseTitle);

        $courseDescription = $crawler->filter('.description_course')->text();
        $this->assertEquals('Данный курс необходим для тестирования', $courseDescription);
    }

    /**
     * @group testUrlSuccess
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
     * @group testUrlNotFound
     * @dataProvider urlNotFound
     */
    public function testUrlNotFound($url): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }
    /**
     * @group testUrlInternalServerError
     * @dataProvider urlInternalServerError
     */
    public function testUrlInternalServerError($url)
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $client->request('GET', $url);
        $this->assertResponseCode(500);
    }
    /**
     * @group testCountCourses
     */
    public function testCountCourses(): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $crawler = $client->request('GET', '/courses/');
        $courseRepository = self::getEntityManager()->getRepository(Course::class);
        $countCourses = count($courseRepository->findAll());
        $this->assertCount($countCourses, $crawler->filter('.open_course'));
    }
    /**
     * @group testCountLessons
     */
    public function testCountLessons(): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();
        $crawler = $client->request('GET', '/courses/');
        $courseRepository = self::getEntityManager()->getRepository(Course::class);
        $courses = $courseRepository->findAll();
        foreach ($courses as $course) {
            $crawler = $client->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();
            $countLessons = count($course->getLessons());
            $this->assertCount($countLessons, $crawler->filter('.edit_lesson'));
        }
    }
    /**
     * @group testCreateBlankFields
     */
    public function testCreateBlankField(): void
    {
        $crawler = $this->adminUser();
        $client = AbstractTest::getClient();

        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.create_course')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'course[code]' => '',
            'course[title]' => 'Тестовый курс',
            'course[description]' => 'Данный курс предназначен для тестирования',
        ]);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('This value should not be blank.', $error->text());

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'course[code]' => 'FFFA',
            'course[title]' => '',
            'course[description]' => 'Данный курс предназначен для тестирования',
        ]);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('This value should not be blank.', $error->text());

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'course[code]' => 'FFFA',
            'course[title]' => 'Данный курс предназначен для тестирования',
            'course[description]' => '',
        ]);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('This value should not be blank.', $error->text());
    }

    /**
     * @group testCreateInvalidLength
     */
    public function testCreateInvalidLength(): void
    {
        $crawler=$this->adminUser();
        $client = AbstractTest::getClient();

        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.create_course')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'course[code]' => 'qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq
            qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq
            qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq',
            'course[title]' => 'Тестовый курс',
            'course[description]' => 'Данный курс предназначен для тестирования',
        ]);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('Код должен иметь не больше 255 символов', $error->text());

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'course[code]' => 'FFFA',
            'course[title]' => 'qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq
            qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq
            qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq',
            'course[description]' => 'Данный курс предназначен для тестирования',
        ]);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('Название курса должно иметь не больше 255 символов', $error->text());
    }
    /**
     * @group testCreateErrorCode
     */
    public function testCreateErrorCode(): void
    {
        $crawler=$this->adminUser();
        $client = AbstractTest::getClient();

        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.create_course')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'course[code]' => 'FFA$%&#$^(',
            'course[title]' => 'Тестовый курс',
            'course[description]' => 'Данный курс предназначен для тестирования',
        ]);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('Код должен содержить только буквы латинского алфавита и цифры', $error->text());

        $button = $crawler->selectButton('Сохранить');
        $form = $button->form([
            'course[code]' => '0001',
            'course[title]' => 'Тестовый курс',
            'course[description]' => 'Данный курс предназначен для тестирования',
        ]);
        $crawler = $client->submit($form);
        $error = $crawler->filter('.invalid-feedback')->first();
        $this->assertEquals('This value is already used.', $error->text());
    }
}
