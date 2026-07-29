%code top {
/*
 * Portions of this parser grammar are derived from UDUNITS-2 lib/parser.y:
 * Copyright 2020 University Corporation for Atmospheric Research
 *
 * This derivative grammar and its modifications are distributed under the
 * Yumemi project license. The UDUNITS-derived portions remain subject to the
 * UCAR License.
 *
 * Copyright (c) anno Domini nostri Jesu Christi MMXXIV-MMXXVI, John Boehr & contributors
 *
 * SPDX-License-Identifier: (AGPL-3.0-only WITH romic-exception) AND UCAR
 *
 * See docs/UDUNITS-COPYRIGHT for copying and redistribution conditions.
 */
}

%define api.parser.class {Parser}
%define api.namespace {jbboehr\Yumemi\Parser}

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

%token T_INTEGER "integer"
%token T_SUPERSCRIPT_INTEGER "superscript integer"
%token T_INVALID_SUPERSCRIPT "superscript sign without digits"
%token T_FLOAT "decimal number"
%token T_DOT "."
%token T_MUL "*"
%token T_DIV "/"
%token T_POW "^"
%token T_SUB "-"
%token T_ADD "+"
%token T_IDENTIFIER "identifier"
%token T_LEFT_PAREN "("
%token T_RIGHT_PAREN ")"
%token T_AT "@"

%%
start:
        exp                                     {  self::setAst($1); }
    ;

exp:
        additive_exp                            { $$ = $1; }
    ;

additive_exp:
        product_exp                             { $$ = $1; }
    |   additive_exp T_ADD product_exp          { $$ = self::makeAdd($1, $3); }
    |   additive_exp T_SUB product_exp          { $$ = self::makeSub($1, $3); }
    ;

product_exp:
        unary_exp                               { $$ = $1; }
    |   product_exp power_exp                   { $$ = self::makeMul($1, $2); }
    |   product_exp T_DOT unary_exp             { $$ = self::makeMul($1, $3); }
    |   product_exp T_MUL unary_exp             { $$ = self::makeMul($1, $3); }
    |   product_exp T_DIV unary_exp             { $$ = self::makeDiv($1, $3); }
    ;

unary_exp:
        power_exp                               { $$ = $1; }
    |   T_SUB unary_exp                         { $$ = self::makeNeg($2); }
    ;

power_exp:
        simple                                  { $$ = $1; }
    |   simple T_POW unary_exp                  { $$ = self::makePow($1, $3); }
    ;

simple:
        number                                  { $$ = $1; }
    |   identifier                              { $$ = $1; }
    |   identifier T_AT number                  { $$ = self::makeAt($1, $3); }
    |   T_LEFT_PAREN exp T_RIGHT_PAREN          { $$ = $2; }
    |   simple T_SUPERSCRIPT_INTEGER %prec T_POW { $$ = self::makePow($1, self::makeSuperscriptInteger($2)); }
    ;

number:
        T_INTEGER                               { $$ = self::makeInteger($1); }
    |   T_FLOAT                                 { $$ = self::makeFloat($1); }
    ;

identifier:
        T_IDENTIFIER                            { $$ = self::makeIdentifier($1); }
    ;
