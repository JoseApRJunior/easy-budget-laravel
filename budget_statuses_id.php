
   Psy\Exception\ParseErrorException 

  PHP Parse error: Syntax error, unexpected T_NS_SEPARATOR on line 1

  at vendor\psy\psysh\src\Exception\ParseErrorException.php:44
     40▕      * @param \PhpParser\Error $e
     41▕      */
     42▕     public static function fromParseError(\PhpParser\Error $e): self
     43▕     {
  ➜  44▕         return new self($e->getRawMessage(), $e->getAttributes());
     45▕     }
     46▕ }
     47▕

  1   vendor\psy\psysh\src\CodeCleaner.php:306
      Psy\Exception\ParseErrorException::fromParseError(Object(PhpParser\Error))

  2   vendor\psy\psysh\src\CodeCleaner.php:240
      Psy\CodeCleaner::parse("<?php echo 'Sample budget records after migration:'; \$budgets = \DB::table('budgets')->limit(5)->get(); foreach(\$budgets as \$budget) { echo "ID: {\$budget-, Status: {\$budget- (Type: " . gettype(\$budget->budget_statuses_id) . ")\n"; }
")

