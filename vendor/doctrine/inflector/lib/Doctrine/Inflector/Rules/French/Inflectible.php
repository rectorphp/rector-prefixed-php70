<?php

declare (strict_types=1);
namespace RectorPrefix20210520\Doctrine\Inflector\Rules\French;

use RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern;
use RectorPrefix20210520\Doctrine\Inflector\Rules\Substitution;
use RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation;
use RectorPrefix20210520\Doctrine\Inflector\Rules\Word;
class Inflectible
{
    /**
     * @return mixed[]
     */
    public static function getSingular()
    {
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/(b|cor|ém|gemm|soupir|trav|vant|vitr)aux$/'), '\\1ail'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/ails$/'), 'ail'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/(journ|chev)aux$/'), '\\1al'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/(bijou|caillou|chou|genou|hibou|joujou|pou|au|eu|eau)x$/'), '\\1'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/s$/'), ''));
    }
    /**
     * @return mixed[]
     */
    public static function getPlural()
    {
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/(s|x|z)$/'), '\\1'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/(b|cor|ém|gemm|soupir|trav|vant|vitr)ail$/'), '\\1aux'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/ail$/'), 'ails'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/al$/'), 'aux'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/(bleu|émeu|landau|lieu|pneu|sarrau)$/'), '\\1s'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/(bijou|caillou|chou|genou|hibou|joujou|pou|au|eu|eau)$/'), '\\1x'));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Transformation(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Pattern('/$/'), 's'));
    }
    /**
     * @return mixed[]
     */
    public static function getIrregular()
    {
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Substitution(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Word('monsieur'), new \RectorPrefix20210520\Doctrine\Inflector\Rules\Word('messieurs')));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Substitution(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Word('madame'), new \RectorPrefix20210520\Doctrine\Inflector\Rules\Word('mesdames')));
        (yield new \RectorPrefix20210520\Doctrine\Inflector\Rules\Substitution(new \RectorPrefix20210520\Doctrine\Inflector\Rules\Word('mademoiselle'), new \RectorPrefix20210520\Doctrine\Inflector\Rules\Word('mesdemoiselles')));
    }
}
