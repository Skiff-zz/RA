<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__FILE__).'/../../TestCase.php';

class Twig_Tests_Node_Expression_Binary_ModTest extends Twig_Tests_Node_TestCase
{
    /**
     * @covers Twig_Node_Expression_Binary_Mod::__construct
     */
    public function testConstructor()
    {
        $left = new Twig_Node_Expression_Constant(1, 0);
        $right = new Twig_Node_Expression_Constant(2, 0);
        $node = new Twig_Node_Expression_Binary_Mod($left, $right, 0);

        $this->assertEquals($left, $node->left);
        $this->assertEquals($right, $node->right);
    }

    /**
     * @covers Twig_Node_Expression_Binary_Mod::compile
     * @covers Twig_Node_Expression_Binary_Mod::operator
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null)
    {
        parent::testCompile($node, $source, $environment);
    }

    public function getTests()
    {
        $left = new Twig_Node_Expression_Constant(1, 0);
        $right = new Twig_Node_Expression_Constant(2, 0);
        $node = new Twig_Node_Expression_Binary_Mod($left, $right, 0);

        return array(
            array($node, '(1) % (2)'),
        );
    }
}