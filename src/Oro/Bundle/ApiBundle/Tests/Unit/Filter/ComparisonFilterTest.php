<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Request\DataType;

class ComparisonFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ComparisonFilter */
    protected $comparisonFilter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $this->comparisonFilter->setSupportedOperators(
            [
                ComparisonFilter::EQ,
                ComparisonFilter::NEQ,
                ComparisonFilter::LT,
                ComparisonFilter::LTE,
                ComparisonFilter::GT,
                ComparisonFilter::GTE,
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The Field must not be empty.
     */
    public function testInvalidArgumentExceptionField()
    {
        $this->comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', ComparisonFilter::EQ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must not be NULL. Field: "fieldName".
     */
    public function testInvalidArgumentExceptionValue()
    {
        $this->comparisonFilter->setField('fieldName');
        $this->comparisonFilter->apply(new Criteria(), new FilterValue('path', null, ComparisonFilter::EQ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported operator: "operator". Field: "fieldName".
     */
    public function testInvalidArgumentExceptionOperator()
    {
        $this->comparisonFilter->setField('fieldName');
        $this->comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', 'operator'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported operator: "!=". Field: "fieldName".
     */
    public function testUnsupportedOperatorWhenOperatorsAreNotSpecified()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');
        $comparisonFilter->apply(new Criteria(), new FilterValue('path', 'value', ComparisonFilter::NEQ));
    }

    public function testFilterWhenOperatorsAreNotSpecified()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setField('fieldName');

        $this->assertEquals(['='], $comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value', ComparisonFilter::EQ));

        $this->assertEquals(
            new Comparison('fieldName', Comparison::EQ, 'value'),
            $criteria->getWhereExpression()
        );
    }

    public function testFilterWhenOnlyEqualOperatorIsSpecified()
    {
        $comparisonFilter = new ComparisonFilter(DataType::INTEGER);
        $comparisonFilter->setSupportedOperators([ComparisonFilter::EQ]);
        $comparisonFilter->setField('fieldName');

        $this->assertEquals(['='], $comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $comparisonFilter->apply($criteria, new FilterValue('path', 'value', ComparisonFilter::EQ));

        $this->assertEquals(
            new Comparison('fieldName', Comparison::EQ, 'value'),
            $criteria->getWhereExpression()
        );
    }

    /**
     * @param string      $fieldName
     * @param bool        $isArrayAllowed
     * @param bool        $isRangeAllowed
     * @param FilterValue $filterValue
     * @param Criteria    $expectation
     *
     * @dataProvider testCaseProvider
     */
    public function testFilter($fieldName, $isArrayAllowed, $isRangeAllowed, $filterValue, $expectation)
    {
        $this->assertNull($this->comparisonFilter->getField());
        $this->comparisonFilter->setField($fieldName);
        $this->assertSame($fieldName, $this->comparisonFilter->getField());

        $this->comparisonFilter->setArrayAllowed(true); //setting to TRUE due parent should allow own check
        $this->comparisonFilter->setRangeAllowed(true); //setting to TRUE due parent should allow own check
        if ($filterValue) {
            $this->assertSame($isArrayAllowed, $this->comparisonFilter->isArrayAllowed($filterValue->getOperator()));
            $this->assertSame($isRangeAllowed, $this->comparisonFilter->isRangeAllowed($filterValue->getOperator()));
        }

        $this->assertEquals(['=', '!=', '<', '<=', '>', '>='], $this->comparisonFilter->getSupportedOperators());

        $criteria = new Criteria();
        $this->comparisonFilter->apply($criteria, $filterValue);

        $this->assertEquals($expectation, $criteria->getWhereExpression());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCaseProvider()
    {
        return [
            'empty filter'                 => [
                'fieldName',  //fieldName
                true, // isArrayAllowed
                true, // isRangeAllowed
                null, // filter
                null // expectation
            ],
            'filter with default operator' => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value'),
                new Comparison('fieldName', Comparison::EQ, 'value')
            ],
            'EQ filter'                    => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::EQ),
                new Comparison('fieldName', Comparison::EQ, 'value')
            ],
            'NEQ filter'                   => [
                'fieldName',
                true,
                true,
                new FilterValue('path', 'value', ComparisonFilter::NEQ),
                new Comparison('fieldName', Comparison::NEQ, 'value')
            ],
            'LT filter'                    => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::LT),
                new Comparison('fieldName', Comparison::LT, 'value')
            ],
            'LTE filter'                   => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::LTE),
                new Comparison('fieldName', Comparison::LTE, 'value')
            ],
            'GT filter'                    => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::GT),
                new Comparison('fieldName', Comparison::GT, 'value')
            ],
            'GTE filter'                   => [
                'fieldName',
                false,
                false,
                new FilterValue('path', 'value', ComparisonFilter::GTE),
                new Comparison('fieldName', Comparison::GTE, 'value')
            ],
            'EQ filter for array'          => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], ComparisonFilter::EQ),
                new Comparison('fieldName', Comparison::IN, new Value(['value1', 'value2']))
            ],
            'NEQ filter for array'         => [
                'fieldName',
                true,
                true,
                new FilterValue('path', ['value1', 'value2'], ComparisonFilter::NEQ),
                new Comparison('fieldName', Comparison::NIN, new Value(['value1', 'value2']))
            ],
            'EQ filter for range'          => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), ComparisonFilter::EQ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('fieldName', Comparison::GTE, 'value1'),
                        new Comparison('fieldName', Comparison::LTE, 'value2')
                    ]
                )
            ],
            'NEQ filter for range'         => [
                'fieldName',
                true,
                true,
                new FilterValue('path', new Range('value1', 'value2'), ComparisonFilter::NEQ),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison('fieldName', Comparison::LT, 'value1'),
                        new Comparison('fieldName', Comparison::GT, 'value2')
                    ]
                )
            ],
        ];
    }
}
