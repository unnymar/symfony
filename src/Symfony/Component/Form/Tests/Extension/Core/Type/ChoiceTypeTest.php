<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Tests\Fixtures\ChoiceSubType;

class ChoiceTypeTest extends \Symfony\Component\Form\Test\TypeTestCase
{
    private $choices = array(
        'Bernhard' => 'a',
        'Fabien' => 'b',
        'Kris' => 'c',
        'Jon' => 'd',
        'Roman' => 'e',
    );

    private $objectChoices;

    protected $groupedChoices = array(
        'Symfony' => array(
            'Bernhard' => 'a',
            'Fabien' => 'b',
            'Kris' => 'c',
        ),
        'Doctrine' => array(
            'Jon' => 'd',
            'Roman' => 'e',
        ),
    );

    protected function setUp()
    {
        parent::setUp();

        $this->objectChoices = array(
            (object) array('id' => 1, 'name' => 'Bernhard'),
            (object) array('id' => 2, 'name' => 'Fabien'),
            (object) array('id' => 3, 'name' => 'Kris'),
            (object) array('id' => 4, 'name' => 'Jon'),
            (object) array('id' => 5, 'name' => 'Roman'),
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->objectChoices = null;
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testChoicesOptionExpectsArrayOrTraversable()
    {
        $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => new \stdClass(),
        ));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testChoiceLoaderOptionExpectsChoiceLoaderInterface()
    {
        $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choice_loader' => new \stdClass(),
        ));
    }

    public function testChoiceListAndChoicesCanBeEmpty()
    {
        $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
        ));
    }

    public function testExpandedChoicesOptionsTurnIntoChildren()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'expanded' => true,
            'choices' => $this->choices,
        ));

        $this->assertCount(count($this->choices), $form, 'Each choice should become a new field');
    }

    public function testPlaceholderPresentOnNonRequiredExpandedSingleChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => $this->choices,
        ));

        $this->assertTrue(isset($form['placeholder']));
        $this->assertCount(count($this->choices) + 1, $form, 'Each choice should become a new field');
    }

    public function testPlaceholderNotPresentIfRequired()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => $this->choices,
        ));

        $this->assertFalse(isset($form['placeholder']));
        $this->assertCount(count($this->choices), $form, 'Each choice should become a new field');
    }

    public function testPlaceholderNotPresentIfMultiple()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'choices' => $this->choices,
        ));

        $this->assertFalse(isset($form['placeholder']));
        $this->assertCount(count($this->choices), $form, 'Each choice should become a new field');
    }

    public function testPlaceholderNotPresentIfEmptyChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => array(
                'Empty' => '',
                'Not empty' => 1,
            ),
        ));

        $this->assertFalse(isset($form['placeholder']));
        $this->assertCount(2, $form, 'Each choice should become a new field');
    }

    public function testExpandedChoicesOptionsAreFlattened()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'expanded' => true,
            'choices' => $this->groupedChoices,
        ));

        $flattened = array();
        foreach ($this->groupedChoices as $choices) {
            $flattened = array_merge($flattened, array_keys($choices));
        }

        $this->assertCount($form->count(), $flattened, 'Each nested choice should become a new field, not the groups');

        foreach ($flattened as $value => $choice) {
            $this->assertTrue($form->has($value), 'Flattened choice is named after it\'s value');
        }
    }

    public function testExpandedChoicesOptionsAreFlattenedObjectChoices()
    {
        $obj1 = (object) array('id' => 1, 'name' => 'Bernhard');
        $obj2 = (object) array('id' => 2, 'name' => 'Fabien');
        $obj3 = (object) array('id' => 3, 'name' => 'Kris');
        $obj4 = (object) array('id' => 4, 'name' => 'Jon');
        $obj5 = (object) array('id' => 5, 'name' => 'Roman');

        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'expanded' => true,
            'choices' => array(
                'Symfony' => array($obj1, $obj2, $obj3),
                'Doctrine' => array($obj4, $obj5),
            ),
            'choice_name' => 'id',
        ));

        $this->assertSame(5, $form->count(), 'Each nested choice should become a new field, not the groups');
        $this->assertTrue($form->has(1));
        $this->assertTrue($form->has(2));
        $this->assertTrue($form->has(3));
        $this->assertTrue($form->has(4));
        $this->assertTrue($form->has(5));
    }

    public function testExpandedCheckboxesAreNeverRequired()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'required' => true,
            'choices' => $this->choices,
        ));

        foreach ($form as $child) {
            $this->assertFalse($child->isRequired());
        }
    }

    public function testExpandedRadiosAreRequiredIfChoiceChildIsRequired()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => $this->choices,
        ));

        foreach ($form as $child) {
            $this->assertTrue($child->isRequired());
        }
    }

    public function testExpandedRadiosAreNotRequiredIfChoiceChildIsNotRequired()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => $this->choices,
        ));

        foreach ($form as $child) {
            $this->assertFalse($child->isRequired());
        }
    }

    public function testSubmitSingleNonExpanded()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit('b');

        $this->assertEquals('b', $form->getData());
        $this->assertEquals('b', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleNonExpandedInvalidChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit('foobar');

        $this->assertNull($form->getData());
        $this->assertEquals('foobar', $form->getViewData());
        $this->assertFalse($form->isSynchronized());
    }

    public function testSubmitSingleNonExpandedNull()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit(null);

        $this->assertNull($form->getData());
        $this->assertSame('', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleNonExpandedNullNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => array(),
        ));

        $form->submit(null);

        $this->assertNull($form->getData());
        $this->assertSame('', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleNonExpandedEmpty()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertSame('', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleNonExpandedEmptyExplicitEmptyChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => array(
                'Empty' => 'EMPTY_CHOICE',
            ),
            'choice_value' => function () {
                return '';
            },
        ));

        $form->submit('');

        $this->assertSame('EMPTY_CHOICE', $form->getData());
        $this->assertSame('', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleNonExpandedEmptyNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => array(),
        ));

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertSame('', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleNonExpandedFalse()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit(false);

        $this->assertNull($form->getData());
        $this->assertSame('', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleNonExpandedFalseNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => array(),
        ));

        $form->submit(false);

        $this->assertNull($form->getData());
        $this->assertSame('', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleNonExpandedObjectChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => false,
            'choices' => $this->objectChoices,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ));

        // "id" value of the second entry
        $form->submit('2');

        $this->assertEquals($this->objectChoices[1], $form->getData());
        $this->assertEquals('2', $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitMultipleNonExpanded()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit(array('a', 'b'));

        $this->assertEquals(array('a', 'b'), $form->getData());
        $this->assertEquals(array('a', 'b'), $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitMultipleNonExpandedEmpty()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit(array());

        $this->assertSame(array(), $form->getData());
        $this->assertSame(array(), $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitMultipleNonExpandedEmptyNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => false,
            'choices' => array(),
        ));

        $form->submit(array());

        $this->assertSame(array(), $form->getData());
        $this->assertSame(array(), $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitMultipleNonExpandedInvalidScalarChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit('foobar');

        $this->assertNull($form->getData());
        $this->assertEquals('foobar', $form->getViewData());
        $this->assertFalse($form->isSynchronized());
    }

    public function testSubmitMultipleNonExpandedInvalidArrayChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => false,
            'choices' => $this->choices,
        ));

        $form->submit(array('a', 'foobar'));

        $this->assertNull($form->getData());
        $this->assertEquals(array('a', 'foobar'), $form->getViewData());
        $this->assertFalse($form->isSynchronized());
    }

    public function testSubmitMultipleNonExpandedObjectChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => false,
            'choices' => $this->objectChoices,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ));

        $form->submit(array('2', '3'));

        $this->assertEquals(array($this->objectChoices[1], $this->objectChoices[2]), $form->getData());
        $this->assertEquals(array('2', '3'), $form->getViewData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleExpandedRequired()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => $this->choices,
        ));

        $form->submit('b');

        $this->assertSame('b', $form->getData());
        $this->assertSame('b', $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertTrue($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertSame('b', $form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSubmitSingleExpandedRequiredInvalidChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => $this->choices,
        ));

        $form->submit('foobar');

        $this->assertNull($form->getData());
        $this->assertSame('foobar', $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertFalse($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSubmitSingleExpandedNonRequired()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => $this->choices,
        ));

        $form->submit('b');

        $this->assertSame('b', $form->getData());
        $this->assertSame('b', $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertFalse($form['placeholder']->getData());
        $this->assertFalse($form[0]->getData());
        $this->assertTrue($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form['placeholder']->getViewData());
        $this->assertNull($form[0]->getViewData());
        $this->assertSame('b', $form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSubmitSingleExpandedNonRequiredInvalidChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => $this->choices,
        ));

        $form->submit('foobar');

        $this->assertNull($form->getData());
        $this->assertSame('foobar', $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertFalse($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSubmitSingleExpandedRequiredNull()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => $this->choices,
        ));

        $form->submit(null);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleExpandedRequiredNullNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => array(),
        ));

        $form->submit(null);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleExpandedRequiredEmpty()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => $this->choices,
        ));

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleExpandedRequiredEmptyNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => array(),
        ));

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleExpandedRequiredFalse()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => $this->choices,
        ));

        $form->submit(false);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleExpandedRequiredFalseNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => true,
            'choices' => array(),
        ));

        $form->submit(false);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleExpandedNonRequiredNull()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => $this->choices,
        ));

        $form->submit(null);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertTrue($form['placeholder']->getData());
        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertSame('', $form['placeholder']->getViewData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleExpandedNonRequiredNullNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => array(),
        ));

        $form->submit(null);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleExpandedNonRequiredEmpty()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => $this->choices,
        ));

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertTrue($form['placeholder']->getData());
        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertSame('', $form['placeholder']->getViewData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleExpandedNonRequiredEmptyNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => array(),
        ));

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleExpandedNonRequiredFalse()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => $this->choices,
        ));

        $form->submit(false);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertTrue($form['placeholder']->getData());
        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertSame('', $form['placeholder']->getViewData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitSingleExpandedNonRequiredFalseNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'required' => false,
            'choices' => array(),
        ));

        $form->submit(false);

        $this->assertNull($form->getData());
        $this->assertNull($form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitSingleExpandedWithEmptyChild()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'choices' => array(
                'Empty' => '',
                'Not empty' => 1,
            ),
        ));

        $form->submit('');

        $this->assertNull($form->getData());
        $this->assertTrue($form->isSynchronized());

        $this->assertTrue($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertSame('', $form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
    }

    public function testSubmitSingleExpandedObjectChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'expanded' => true,
            'choices' => $this->objectChoices,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ));

        $form->submit('2');

        $this->assertSame($this->objectChoices[1], $form->getData());
        $this->assertTrue($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertTrue($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertSame('2', $form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSubmitMultipleExpanded()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'choices' => $this->choices,
        ));

        $form->submit(array('a', 'c'));

        $this->assertSame(array('a', 'c'), $form->getData());
        $this->assertSame(array('a', 'c'), $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $this->assertTrue($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertTrue($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertSame('a', $form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertSame('c', $form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSubmitMultipleExpandedInvalidScalarChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'choices' => $this->choices,
        ));

        $form->submit('foobar');

        $this->assertNull($form->getData());
        $this->assertSame('foobar', $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertFalse($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSubmitMultipleExpandedInvalidArrayChoice()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'choices' => $this->choices,
        ));

        $form->submit(array('a', 'foobar'));

        $this->assertNull($form->getData());
        $this->assertSame(array('a', 'foobar'), $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertFalse($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSubmitMultipleExpandedEmpty()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'choices' => $this->choices,
        ));

        $form->submit(array());

        $this->assertSame(array(), $form->getData());
        $this->assertTrue($form->isSynchronized());

        $this->assertFalse($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertNull($form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    // In edge cases (for example, when choices are loaded dynamically by a
    // loader), the choices may be empty. Make sure to behave the same as when
    // choices are available.
    public function testSubmitMultipleExpandedEmptyNoChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'choices' => array(),
        ));

        $form->submit(array());

        $this->assertSame(array(), $form->getData());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitMultipleExpandedWithEmptyChild()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'choices' => array(
                'Empty' => '',
                'Not Empty' => 1,
                'Not Empty 2' => 2,
            ),
        ));

        $form->submit(array('', '2'));

        $this->assertSame(array('', 2), $form->getData());
        $this->assertTrue($form->isSynchronized());

        $this->assertTrue($form[0]->getData());
        $this->assertFalse($form[1]->getData());
        $this->assertTrue($form[2]->getData());
        $this->assertSame('', $form[0]->getViewData());
        $this->assertNull($form[1]->getViewData());
        $this->assertSame('2', $form[2]->getViewData());
    }

    public function testSubmitMultipleExpandedObjectChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => true,
            'choices' => $this->objectChoices,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ));

        $form->submit(array('1', '2'));

        $this->assertSame(array($this->objectChoices[0], $this->objectChoices[1]), $form->getData());
        $this->assertTrue($form->isSynchronized());

        $this->assertTrue($form[0]->getData());
        $this->assertTrue($form[1]->getData());
        $this->assertFalse($form[2]->getData());
        $this->assertFalse($form[3]->getData());
        $this->assertFalse($form[4]->getData());
        $this->assertSame('1', $form[0]->getViewData());
        $this->assertSame('2', $form[1]->getViewData());
        $this->assertNull($form[2]->getViewData());
        $this->assertNull($form[3]->getViewData());
        $this->assertNull($form[4]->getViewData());
    }

    public function testSingleSelectedObjectChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', $this->objectChoices[3], array(
            'multiple' => false,
            'expanded' => false,
            'choices' => $this->objectChoices,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ));

        $view = $form->createView();
        $selectedChecker = $view->vars['is_selected'];

        $this->assertTrue($selectedChecker($view->vars['choices'][3]->value, $view->vars['value']));
        $this->assertFalse($selectedChecker($view->vars['choices'][1]->value, $view->vars['value']));
    }

    public function testMultipleSelectedObjectChoices()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', array($this->objectChoices[3]), array(
            'multiple' => true,
            'expanded' => false,
            'choices' => $this->objectChoices,
            'choice_label' => 'name',
            'choice_value' => 'id',
        ));

        $view = $form->createView();
        $selectedChecker = $view->vars['is_selected'];

        $this->assertTrue($selectedChecker($view->vars['choices'][3]->value, $view->vars['value']));
        $this->assertFalse($selectedChecker($view->vars['choices'][1]->value, $view->vars['value']));
    }

    public function testPassRequiredToView()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertTrue($view->vars['required']);
    }

    public function testPassNonRequiredToView()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'required' => false,
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertFalse($view->vars['required']);
    }

    public function testPassMultipleToView()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertTrue($view->vars['multiple']);
    }

    public function testPassExpandedToView()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'expanded' => true,
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertTrue($view->vars['expanded']);
    }

    public function testPassChoiceTranslationDomainToView()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertNull($view->vars['choice_translation_domain']);
    }

    public function testChoiceTranslationDomainWithTrueValueToView()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => $this->choices,
            'choice_translation_domain' => true,
        ));
        $view = $form->createView();

        $this->assertNull($view->vars['choice_translation_domain']);
    }

    public function testDefaultChoiceTranslationDomainIsSameAsTranslationDomainToView()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => $this->choices,
            'translation_domain' => 'foo',
        ));
        $view = $form->createView();

        $this->assertEquals('foo', $view->vars['choice_translation_domain']);
    }

    public function testInheritChoiceTranslationDomainFromParent()
    {
        $view = $this->factory
            ->createNamedBuilder('parent', 'Symfony\Component\Form\Extension\Core\Type\FormType', null, array(
                'translation_domain' => 'domain',
            ))
            ->add('child', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => array(),
            ))
            ->getForm()
            ->createView();

        $this->assertEquals('domain', $view['child']->vars['choice_translation_domain']);
    }

    public function testPlaceholderIsNullByDefaultIfRequired()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'required' => true,
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertNull($view->vars['placeholder']);
    }

    public function testPlaceholderIsEmptyStringByDefaultIfNotRequired()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => false,
            'required' => false,
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertSame('', $view->vars['placeholder']);
    }

    /**
     * @dataProvider getOptionsWithPlaceholder
     */
    public function testPassPlaceholderToView($multiple, $expanded, $required, $placeholder, $viewValue)
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => $multiple,
            'expanded' => $expanded,
            'required' => $required,
            'placeholder' => $placeholder,
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertEquals($viewValue, $view->vars['placeholder']);
        $this->assertFalse($view->vars['placeholder_in_choices']);
    }

    /**
     * @dataProvider getOptionsWithPlaceholder
     */
    public function testDontPassPlaceholderIfContainedInChoices($multiple, $expanded, $required, $placeholder, $viewValue)
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => $multiple,
            'expanded' => $expanded,
            'required' => $required,
            'placeholder' => $placeholder,
            'choices' => array('A' => 'a', 'Empty' => ''),
        ));
        $view = $form->createView();

        $this->assertNull($view->vars['placeholder']);
        $this->assertTrue($view->vars['placeholder_in_choices']);
    }

    public function getOptionsWithPlaceholder()
    {
        return array(
            // single non-expanded
            array(false, false, false, 'foobar', 'foobar'),
            array(false, false, false, '', ''),
            array(false, false, false, null, null),
            array(false, false, false, false, null),
            array(false, false, true, 'foobar', 'foobar'),
            array(false, false, true, '', ''),
            array(false, false, true, null, null),
            array(false, false, true, false, null),
            // single expanded
            array(false, true, false, 'foobar', 'foobar'),
            // radios should never have an empty label
            array(false, true, false, '', 'None'),
            array(false, true, false, null, null),
            array(false, true, false, false, null),
            array(false, true, true, 'foobar', 'foobar'),
            // radios should never have an empty label
            array(false, true, true, '', 'None'),
            array(false, true, true, null, null),
            array(false, true, true, false, null),
            // multiple non-expanded
            array(true, false, false, 'foobar', null),
            array(true, false, false, '', null),
            array(true, false, false, null, null),
            array(true, false, false, false, null),
            array(true, false, true, 'foobar', null),
            array(true, false, true, '', null),
            array(true, false, true, null, null),
            array(true, false, true, false, null),
            // multiple expanded
            array(true, true, false, 'foobar', null),
            array(true, true, false, '', null),
            array(true, true, false, null, null),
            array(true, true, false, false, null),
            array(true, true, true, 'foobar', null),
            array(true, true, true, '', null),
            array(true, true, true, null, null),
            array(true, true, true, false, null),
        );
    }

    public function testPassChoicesToView()
    {
        $choices = array('A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd');
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => $choices,
        ));
        $view = $form->createView();

        $this->assertEquals(array(
            new ChoiceView('a', 'a', 'A'),
            new ChoiceView('b', 'b', 'B'),
            new ChoiceView('c', 'c', 'C'),
            new ChoiceView('d', 'd', 'D'),
        ), $view->vars['choices']);
    }

    public function testPassPreferredChoicesToView()
    {
        $choices = array('A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd');
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => $choices,
            'preferred_choices' => array('b', 'd'),
        ));
        $view = $form->createView();

        $this->assertEquals(array(
            0 => new ChoiceView('a', 'a', 'A'),
            2 => new ChoiceView('c', 'c', 'C'),
        ), $view->vars['choices']);
        $this->assertEquals(array(
            1 => new ChoiceView('b', 'b', 'B'),
            3 => new ChoiceView('d', 'd', 'D'),
        ), $view->vars['preferred_choices']);
    }

    public function testPassHierarchicalChoicesToView()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => $this->groupedChoices,
            'preferred_choices' => array('b', 'd'),
        ));
        $view = $form->createView();

        $this->assertEquals(array(
            'Symfony' => new ChoiceGroupView('Symfony', array(
                0 => new ChoiceView('a', 'a', 'Bernhard'),
                2 => new ChoiceView('c', 'c', 'Kris'),
            )),
            'Doctrine' => new ChoiceGroupView('Doctrine', array(
                4 => new ChoiceView('e', 'e', 'Roman'),
            )),
        ), $view->vars['choices']);
        $this->assertEquals(array(
            'Symfony' => new ChoiceGroupView('Symfony', array(
                1 => new ChoiceView('b', 'b', 'Fabien'),
            )),
            'Doctrine' => new ChoiceGroupView('Doctrine', array(
                3 => new ChoiceView('d', 'd', 'Jon'),
            )),
        ), $view->vars['preferred_choices']);
    }

    public function testPassChoiceDataToView()
    {
        $obj1 = (object) array('value' => 'a', 'label' => 'A');
        $obj2 = (object) array('value' => 'b', 'label' => 'B');
        $obj3 = (object) array('value' => 'c', 'label' => 'C');
        $obj4 = (object) array('value' => 'd', 'label' => 'D');
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => array($obj1, $obj2, $obj3, $obj4),
            'choice_label' => 'label',
            'choice_value' => 'value',
        ));
        $view = $form->createView();

        $this->assertEquals(array(
            new ChoiceView($obj1, 'a', 'A'),
            new ChoiceView($obj2, 'b', 'B'),
            new ChoiceView($obj3, 'c', 'C'),
            new ChoiceView($obj4, 'd', 'D'),
        ), $view->vars['choices']);
    }

    public function testAdjustFullNameForMultipleNonExpanded()
    {
        $form = $this->factory->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'multiple' => true,
            'expanded' => false,
            'choices' => $this->choices,
        ));
        $view = $form->createView();

        $this->assertSame('name[]', $view->vars['full_name']);
    }

    // https://github.com/symfony/symfony/issues/3298
    public function testInitializeWithEmptyChoices()
    {
        $this->factory->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => array(),
        ));
    }

    public function testInitializeWithDefaultObjectChoice()
    {
        $obj1 = (object) array('value' => 'a', 'label' => 'A');
        $obj2 = (object) array('value' => 'b', 'label' => 'B');
        $obj3 = (object) array('value' => 'c', 'label' => 'C');
        $obj4 = (object) array('value' => 'd', 'label' => 'D');

        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => array($obj1, $obj2, $obj3, $obj4),
            'choice_label' => 'label',
            'choice_value' => 'value',
            // Used to break because "data_class" was inferred, which needs to
            // remain null in every case (because it refers to the view format)
            'data' => $obj3,
        ));

        // Trigger data initialization
        $form->getViewData();
    }

    /**
     * This covers the case when:
     *  - Custom choice type added after a choice type.
     *  - Custom type is expanded.
     *  - Custom type replaces 'choices' normalizer with a custom one.
     * In this case, custom type should not inherit labels from the first added choice type.
     */
    public function testCustomChoiceTypeDoesNotInheritChoiceLabels()
    {
        $builder = $this->factory->createBuilder();
        $builder->add('choice', 'choice', array(
                'choices' => array(
                    '1' => '1',
                    '2' => '2',
                ),
            )
        );
        $builder->add('subChoice', new ChoiceSubType());
        $form = $builder->getForm();

        // The default 'choices' normalizer would fill the $choiceLabels, but it has been replaced
        // in the custom choice type, so $choiceLabels->labels remains empty array.
        // In this case the 'choice_label' closure returns null and not the closure from the first choice type.
        $this->assertNull($form->get('subChoice')->getConfig()->getOption('choice_label'));
    }
}
