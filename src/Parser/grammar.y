%define api.parser.class {Parser}
%define api.namespace {jbboehr\IudexMensurarumMysteriorum\Parser}

%define api.position.type {int}
%define api.location.type {Location}
%locations

%code parser {
    use ParserUtils;
    private Ast $ast;
    public function setAst(Ast $ast): void { $this->ast = $ast; }
    public function getAst(): Ast { return $this->ast; }
}

%define parse.error detailed

%token T_INTEGER
%token T_FLOAT
%token T_DOT
%token T_MUL '*'
%token T_DIV '/'
%token T_POW '^'
%token T_SUB '-'
%token T_ADD '+'
%token T_IDENTIFIER
%token T_LEFT_PAREN '('
%token T_RIGHT_PAREN ')'
%token T_AT '@'

%precedence LOW
%left T_DOT
%left T_ADD T_SUB
%left T_MUL T_DIV
%precedence NEG
%right T_POW
%left T_AT

%%
start:
        exp                                     {  self::setAst($1); }
    ;

exp:
        simple                                  { $$ = $1; }
    |   exp T_ADD exp                           { $$ = self::makeAdd($1, $3); }
    |   exp T_SUB exp                           { $$ = self::makeSub($1, $3); }
    |   exp T_MUL exp                           { $$ = self::makeMul($1, $3); }
    |   exp T_DIV exp                           { $$ = self::makeDiv($1, $3); }
    |   exp T_POW exp                           { $$ = self::makePow($1, $3); }
    |   T_SUB exp %prec NEG                     { $$ = self::makeNeg($2); }
    |   exp T_DOT exp                           { $$ = self::makeMul($1, $3); }
    |   seq %prec LOW                           { $$ = self::listToMul($1); }
    |   identifier T_AT number                  { $$ = self::makeAt($1, $3); }
    ;

seq:
        simple simple %prec LOW                 { $$ = [$1, $2]; }
    |   seq simple %prec LOW                    { $$ = $1; $$[] = $2; }
    |   seq T_POW simple                        { $$ = self::fudgeSeqPow($1, $3); }
    |   seq T_POW T_SUB number                  { $$ = self::fudgeSeqPow($1, self::makeNeg($4)); }
    ;

simple:
        number                                  { $$ = $1; }
    |   identifier                              { $$ = $1; }
    |   T_LEFT_PAREN exp T_RIGHT_PAREN          { $$ = $2; }
    ;

number:
        T_INTEGER                               { $$ = self::makeInteger($1); }
    |   T_FLOAT                                 { $$ = self::makeFloat($1); }
    ;

identifier:
        T_IDENTIFIER                            { $$ = self::makeIdentifier($1); }
    ;
