<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\ValueObject\{Label, Repository};
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user1 = new User('1');
        $user2 = new User('2');
        $user3 = new User('3');

        $repository1 = new Repository('https://github.com/symfony/symfony');
        $repository2 = new Repository('https://github.com/kubk/wave-algo');
        $repository3 = new Repository('https://github.com/kubk/image-pixel-manipulation');

        foreach ([new Label('easy pick'), new Label('docs'), new Label('help wanted')] as $label) {
            $user1->subscribeForLabel($repository1, $label);
        }
        $user1->subscribeForLabel($repository2, new Label('easy pick'));

        foreach ([new Label('easy pick'), new Label('help wanted')] as $label) {
            $user2->subscribeForLabel($repository1, $label);
            $user2->subscribeForLabel($repository2, $label);
        }

        $user3->subscribeForLabel($repository1, new Label('easy pick'));
        $user3->subscribeForLabel($repository3, new Label('docs'));

        $manager->persist($user1);
        $manager->persist($user2);
        $manager->persist($user3);

        $manager->flush();
    }
}
