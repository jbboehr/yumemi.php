<?php
/* A Bison parser, made by GNU Bison 3.8.2.  */

/* Skeleton implementation for Bison LALR(1) parsers in PHP

   Copyright (C) 2007-2015, 2018-2021 Free Software Foundation, Inc.

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <https://www.gnu.org/licenses/>.  */

/* As a special exception, you may create a larger work that contains
   part or all of the Bison parser skeleton and distribute that work
   under terms of your choice, so long as that work isn't itself a
   parser generator using the skeleton or a modified version thereof
   as a parser skeleton.  Alternatively, if you modify or redistribute
   the parser skeleton itself, you may (at your option) remove this
   special exception, which will cause the skeleton and the resulting
   Bison output files to be licensed under the GNU General Public
   License without this special exception.

   This special exception was added by the Free Software Foundation in
   version 2.2 of Bison.  */

/* DO NOT RELY ON FEATURES THAT ARE NOT DOCUMENTED in the manual,
   especially those whose name start with YY_ or yy_.  They are
   private implementation details that can be changed or removed.  */

namespace jbboehr\Yumemi\Parser;




/* "%code top" blocks.  */
/* "src/Parser/grammar.y":1  */

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

/* "src/Parser/Parser.php":62  */


/**
 * A Bison parser, automatically generated from <tt>grammar.y</tt>.
 *
 * @author LALR (1) parser skeleton written by Paolo Bonzini.
 * Port to PHP language was done by Anton Sukhachev <mrsuh6@gmail.com>.
 */

 /**
   * Communication interface between the scanner and the Bison-generated
   * parser <tt>Parser</tt>.
   */
interface LexerInterface {
    /* Token kinds.  */
    /** Token "end of file", to be returned by the scanner.  */
    public const YYEOF = 0;
    /** Token error, to be returned by the scanner.  */
    public const YYerror = 256;
    /** Token "invalid token", to be returned by the scanner.  */
    public const YYUNDEF = 257;
    /** Token "integer", to be returned by the scanner.  */
    public const T_INTEGER = 258;
    /** Token "superscript integer", to be returned by the scanner.  */
    public const T_SUPERSCRIPT_INTEGER = 259;
    /** Token "superscript sign without digits", to be returned by the scanner.  */
    public const T_INVALID_SUPERSCRIPT = 260;
    /** Token "decimal number", to be returned by the scanner.  */
    public const T_FLOAT = 261;
    /** Token ".", to be returned by the scanner.  */
    public const T_DOT = 262;
    /** Token "*", to be returned by the scanner.  */
    public const T_MUL = 263;
    /** Token "/", to be returned by the scanner.  */
    public const T_DIV = 264;
    /** Token "^", to be returned by the scanner.  */
    public const T_POW = 265;
    /** Token "-", to be returned by the scanner.  */
    public const T_SUB = 266;
    /** Token "+", to be returned by the scanner.  */
    public const T_ADD = 267;
    /** Token "identifier", to be returned by the scanner.  */
    public const T_IDENTIFIER = 268;
    /** Token "(", to be returned by the scanner.  */
    public const T_LEFT_PAREN = 269;
    /** Token ")", to be returned by the scanner.  */
    public const T_RIGHT_PAREN = 270;
    /** Token "@", to be returned by the scanner.  */
    public const T_AT = 271;
    /** Token "malformed number", to be returned by the scanner.  */
    public const T_INVALID_NUMBER = 272;



    /**
     * Method to retrieve the beginning position of the last scanned token.
     * @return int the position at which the last scanned token starts.
     */
    public function getStartPos(): int;

    /**
     * Method to retrieve the ending position of the last scanned token.
     * @return int the first position beyond the last scanned token.
     */
    public function getEndPos(): int;

    /**
     * Method to retrieve the semantic value of the last scanned token.
     * @return mixed the semantic value of the last scanned token.
     */
     public function getLVal();

    /**
     * Entry point for the scanner.  Returns the token identifier corresponding
     * to the next token and prepares to return the semantic value
     * and beginning/ending positions of the token.
     * @return int the token identifier corresponding to the next token.
     */
    public function yylex(): int;

    /**
     * Emit an error referring to the given locationin a user-defined way.
     *
     * @param Location $location The location of the element to which the
     *                error message is related.
     * @param string $message The string for the error message.
     */
     public function yyerror(?Location $location, string $message): void;


  }




  /**
   * Information needed to get the list of expected tokens and to forge
   * a syntax error diagnostic.
   */
  class Context {
    public function __construct(Parser $parser, YYStack $stack, SymbolKind $token, Location $loc) {
      $this->yyparser = $parser;
      $this->yystack = $stack;
      $this->yytoken = $token;
      $this->yylocation = $loc;
    }

    private Parser $yyparser;
    private YYStack $yystack;


    /**
     * The symbol kind of the lookahead token.
     */
    public function getToken(): SymbolKind {
      return $this->yytoken;
    }

    private SymbolKind $yytoken;

    /**
     * The location of the lookahead.
     */
    public function getLocation(): Location {
      return $this->yylocation;
    }

    private Location $yylocation;
    public const NTOKENS = Parser::YYNTOKENS;

    /**
     * Put in YYARG at most YYARGN of the expected tokens given the
     * current YYCTX, and return the number of tokens stored in YYARG.  If
     * YYARG is null, return the number of expected tokens (guaranteed to
     * be less than YYNTOKENS).
     * @param SymbolKind[] $yyarg
     */
    public function getExpectedTokens(array &$yyarg, int $yyoffset, int $yyargn): int {
      $yycount = $yyoffset;
      for ($yyx = 0; $yyx < Parser::YYNTOKENS; ++$yyx)
        {
          /** @var SymbolKind $yysym */
          $yysym = new SymbolKind($yyx);
          if ($yysym->getCode() !== SymbolKind::S_YYerror
              && $yysym->getCode() !== SymbolKind::S_YYUNDEF
              && $this->yyparser->yylacCheck($this->yystack, $yysym))
            {
              if ($yyarg === null) {
                $yycount += 1;
              } else if ($yycount === $yyargn) {
                return 0;
              } else {
                $yyarg[$yycount++] = $yysym;
              }
            }
        }
      if ($yyarg !== null && $yycount === $yyoffset && $yyoffset < $yyargn)
        $yyarg[$yycount] = null;
      return $yycount - $yyoffset;
    }
  }

  class YYStack {
    private array $stateStack = [];
    /** @var Location[] */
    private array $locStack = [];
    private array $valueStack = [];

    public int $height = -1;

    /**
     * @param mixed $value
     */
    public function push(int $state, $value, Location $loc): void {
      $this->height++;

      $this->stateStack[$this->height] = $state;
      $this->locStack[$this->height] = $loc;
      $this->valueStack[$this->height] = $value;
    }

    public function pop(int $num = 1): void {
      $this->height -= $num;
    }

    public function &stateAt(int $i): int {
      return $this->stateStack[$this->height - $i];
    }


    public function &locationAt(int $i): Location {
      return $this->locStack[$this->height - $i];
    }

    /**
     * @return mixed
     */
    public function &valueAt(int $i) {
      return $this->valueStack[$this->height - $i];
    }

    // Print the state stack on the debug stream.
    public function print($resource): void {
      fputs($resource,"Stack now");
      for ($i = 0; $i <= $this->height; $i++) {
        fputs($resource, ' ' . $this->stateStack[$i]);
      }
      fputs($resource, PHP_EOL);
    }
  }


  class SymbolKind
  {
    public const S_YYEOF = 0;      /* "end of file"  */
    public const S_YYerror = 1;    /* error  */
    public const S_YYUNDEF = 2;    /* "invalid token"  */
    public const S_T_INTEGER = 3;  /* "integer"  */
    public const S_T_SUPERSCRIPT_INTEGER = 4; /* "superscript integer"  */
    public const S_T_INVALID_SUPERSCRIPT = 5; /* "superscript sign without digits"  */
    public const S_T_FLOAT = 6;    /* "decimal number"  */
    public const S_T_DOT = 7;      /* "."  */
    public const S_T_MUL = 8;      /* "*"  */
    public const S_T_DIV = 9;      /* "/"  */
    public const S_T_POW = 10;     /* "^"  */
    public const S_T_SUB = 11;     /* "-"  */
    public const S_T_ADD = 12;     /* "+"  */
    public const S_T_IDENTIFIER = 13; /* "identifier"  */
    public const S_T_LEFT_PAREN = 14; /* "("  */
    public const S_T_RIGHT_PAREN = 15; /* ")"  */
    public const S_T_AT = 16;      /* "@"  */
    public const S_T_INVALID_NUMBER = 17; /* "malformed number"  */
    public const S_YYACCEPT = 18;  /* $accept  */
    public const S_start = 19;     /* start  */
    public const S_exp = 20;       /* exp  */
    public const S_additive_exp = 21; /* additive_exp  */
    public const S_product_exp = 22; /* product_exp  */
    public const S_unary_exp = 23; /* unary_exp  */
    public const S_power_exp = 24; /* power_exp  */
    public const S_simple = 25;    /* simple  */
    public const S_number = 26;    /* number  */
    public const S_signed_number = 27; /* signed_number  */
    public const S_identifier = 28; /* identifier  */


    private int $yycode;

    public function __construct(int $yycode) {
      $this->yycode = $yycode;
    }

    public function getCode(): int {
        return $this->yycode;
    }


    private const NAMES = array("end of file", "error", "invalid token", "integer",
  "superscript integer", "superscript sign without digits",
  "decimal number", ".", "*", "/", "^", "-", "+", "identifier", "(", ")",
  "@", "malformed number", "\$accept", "start", "exp", "additive_exp",
  "product_exp", "unary_exp", "power_exp", "simple", "number",
  "signed_number", "identifier", null);

    public function getName(): string {
      return self::NAMES[$this->yycode];
    }
  }



  /**
   * A class defining a pair of positions.  Positions, defined by the
   * <code>int</code> class, denote a point in the input.
   * Locations represent a part of the input through the beginning
   * and ending positions.
   */
  class Location {
    /**
     * The first, inclusive, position in the range.
     */
    public ?int $begin = null;

    /**
     * The first position beyond the range.
     */
    public ?int $end = null;

    /**
     * Create a <code>Location</code> from the endpoints of the range.
     * @param int $begin The first position included in the range.
     * @param int $end   The first position beyond the range.
     */
    public function __construct(?int $begin = null, ?int $end = null) {
      $this->begin = $begin;
      $this->end = $end;
    }

    /**
     * Print a representation of the location.  For this to be correct,
     * <code>int</code> should override the <code>equals</code>
     * method.
     */
    public function __toString(): string {
       return sprintf('%s-%s', $this->begin, $this->end);
    }
  }


class Parser
{
  /** Version number for the Bison executable that generated this parser.  */
  public const BISON_VERSION = "3.8.2";

  /** Name of the skeleton that generated this parser.  */
  public const BISON_SKELETON = "./php-skel.m4";

/* "%code parser" blocks.  */
/* "src/Parser/grammar.y":25  */

    use ParserUtils;
    private Ast $ast;
    public function setAst(Ast $ast): void { $this->ast = $ast; }
    public function getAst(): Ast { return $this->ast; }

/* "src/Parser/Parser.php":387  */



  /**
   * True if verbose error messages are enabled.
   */
  private bool $yyErrorVerbose = true;

  /**
   * Whether verbose error messages are enabled.
   */
  public function getErrorVerbose(): bool
    {
        return $this->yyErrorVerbose;
    }

  /**
   * Set the verbosity of error messages.
   * @param verbose True to request verbose error messages.
   */
 public function setErrorVerbose(bool $verbose): void
 {
     $this->yyErrorVerbose = $verbose;
 }



  private function yylloc(YYStack $rhs, int $n): Location
  {
    if (0 < $n)
      return new Location($rhs->locationAt($n - 1)->begin, $rhs->locationAt(0)->end);
    else
      return new Location($rhs->locationAt(0)->end, $rhs->locationAt(0)->end);
  }

  /**
   * The object doing lexical analysis for us.
   */
  private LexerInterface $yylexer;




  /**
   * Instantiates the Bison-generated parser.
   * @param LexerInterface $lexer The scanner that will supply tokens to the parser.
   */
  public function __construct(LexerInterface $lexer)
  {

    /** @var int[] */
    $this->yylacStack = [];
    $this->yylacEstablished = false;
    $this->yylexer = $lexer;
    $this->yystack          = new YYStack();
    
    $this->yylloc           = new Location();

  }




  private int $yynerrs = 0;

  /**
   * The number of syntax errors so far.
   */
  public function getNumberOfErrors(): int { return $this->yynerrs; }

  /**
   * Print an error message via the lexer.
   * Use a <code>null</code> location.
   * @param msg The error message.
   */
  public function yyerror(?Location $location, string $msg): void {
      $this->yylexer->yyerror($location, $msg);
  }


  /**
   * Returned by a Bison action in order to stop the parsing process and
   * return success (<tt>true</tt>).
   */
  public const YYACCEPT = 0;

  /**
   * Returned by a Bison action in order to stop the parsing process and
   * return failure (<tt>false</tt>).
   */
  public const YYABORT = 1;



  /**
   * Returned by a Bison action in order to start error recovery without
   * printing an error message.
   */
  public const YYERROR = 2;

  /**
   * Internal return codes that are not supported for user semantic
   * actions.
   */
  private const YYERRLAB = 3;
  private const YYNEWSTATE = 4;
  private const YYDEFAULT = 5;
  private const YYREDUCE = 6;
  private const YYERRLAB1 = 7;
  private const YYRETURN = 8;


  private int $yyerrstatus = 0;

    /**
     * Lookahead token kind.
     */
    private int $yychar = Parser::YYEMPTY;
    /**
     * Lookahead symbol kind.
     */
    private ?SymbolKind $yytoken = null;

    /* State.  */
    private int $yyn     = 0;
    private int $yylen   = 0;
    private int $yystate = 0;
    private YYStack $yystack;
    private int $label = Parser::YYNEWSTATE;


    /* The location where the error started.  */
    private ?Location $yyerrloc = null;

    /* Location. */
    private Location $yylloc;

    /**
     * Semantic value of the lookahead.
     * @var mixed
     */
    private $yylval = null;

  /**
   * Whether error recovery is being done.  In this state, the parser
   * reads token until it reaches a known state, and then restarts normal
   * operation.
   */
  public function recovering(): bool
  {
    return $this->yyerrstatus === 0;
  }

  /** Compute post-reduction state.
   * @param yystate   the current state
   * @param yysym     the nonterminal to push on the stack
   */
  private function yyLRGotoState(int $yystate, int $yysym): int {

    $yyr = $this->yypgoto[$yysym - Parser::YYNTOKENS] + $yystate;
    if (0 <= $yyr && $yyr <= Parser::YYLAST && $this->yycheck[$yyr] === $yystate)
      return $this->yytable[$yyr];
    else
      return $this->yydefgoto[$yysym - Parser::YYNTOKENS];
  }

  private function yyaction(int $yyn, YYStack $yystack, int $yylen): int
  {
    /* If YYLEN is nonzero, implement the default value of the action:
       '$$ = $1'.  Otherwise, use the top of the stack.

       Otherwise, the following line sets YYVAL to garbage.
       This behavior is undocumented and Bison
       users should not rely upon it.  */
     /** @var mixed $yyval */
     $yyval = (0 < $yylen) ? $yystack->valueAt($yylen - 1) : $yystack->valueAt(0);
     /** @var Location */
     $yyloc = $this->yylloc($yystack, $yylen);

    switch ($yyn)
      {
          case 2: /* start: exp  */
    /* "src/Parser/grammar.y":53  */
                                                {  self::setAst($yystack->valueAt(0)); };
  break;


  case 3: /* exp: additive_exp  */
    /* "src/Parser/grammar.y":57  */
                                                { $yyval = $yystack->valueAt(0); };
  break;


  case 4: /* additive_exp: product_exp  */
    /* "src/Parser/grammar.y":61  */
                                                { $yyval = $yystack->valueAt(0); };
  break;


  case 5: /* additive_exp: additive_exp "+" product_exp  */
    /* "src/Parser/grammar.y":62  */
                                                { $yyval = self::makeAdd($yystack->valueAt(2), $yystack->valueAt(0), ($yyloc)); };
  break;


  case 6: /* additive_exp: additive_exp "-" product_exp  */
    /* "src/Parser/grammar.y":63  */
                                                { $yyval = self::makeSub($yystack->valueAt(2), $yystack->valueAt(0), ($yyloc)); };
  break;


  case 7: /* product_exp: unary_exp  */
    /* "src/Parser/grammar.y":67  */
                                                { $yyval = $yystack->valueAt(0); };
  break;


  case 8: /* product_exp: product_exp power_exp  */
    /* "src/Parser/grammar.y":68  */
                                                { $yyval = self::makeMul($yystack->valueAt(1), $yystack->valueAt(0), ($yyloc)); };
  break;


  case 9: /* product_exp: product_exp "." unary_exp  */
    /* "src/Parser/grammar.y":69  */
                                                { $yyval = self::makeMul($yystack->valueAt(2), $yystack->valueAt(0), ($yyloc)); };
  break;


  case 10: /* product_exp: product_exp "*" unary_exp  */
    /* "src/Parser/grammar.y":70  */
                                                { $yyval = self::makeMul($yystack->valueAt(2), $yystack->valueAt(0), ($yyloc)); };
  break;


  case 11: /* product_exp: product_exp "/" unary_exp  */
    /* "src/Parser/grammar.y":71  */
                                                { $yyval = self::makeDiv($yystack->valueAt(2), $yystack->valueAt(0), ($yyloc)); };
  break;


  case 12: /* unary_exp: power_exp  */
    /* "src/Parser/grammar.y":75  */
                                                { $yyval = $yystack->valueAt(0); };
  break;


  case 13: /* unary_exp: "-" unary_exp  */
    /* "src/Parser/grammar.y":76  */
                                                { $yyval = self::makeNeg($yystack->valueAt(0), ($yyloc)); };
  break;


  case 14: /* power_exp: simple  */
    /* "src/Parser/grammar.y":80  */
                                                { $yyval = $yystack->valueAt(0); };
  break;


  case 15: /* power_exp: simple "^" unary_exp  */
    /* "src/Parser/grammar.y":81  */
                                                { $yyval = self::makePow($yystack->valueAt(2), $yystack->valueAt(0), ($yyloc)); };
  break;


  case 16: /* simple: number  */
    /* "src/Parser/grammar.y":85  */
                                                { $yyval = $yystack->valueAt(0); };
  break;


  case 17: /* simple: identifier  */
    /* "src/Parser/grammar.y":86  */
                                                { $yyval = $yystack->valueAt(0); };
  break;


  case 18: /* simple: identifier "@" signed_number  */
    /* "src/Parser/grammar.y":87  */
                                                { $yyval = self::makeAt($yystack->valueAt(2), $yystack->valueAt(0), ($yyloc)); };
  break;


  case 19: /* simple: "(" exp ")"  */
    /* "src/Parser/grammar.y":88  */
                                                { $yyval = $yystack->valueAt(1); };
  break;


  case 20: /* simple: simple "superscript integer"  */
    /* "src/Parser/grammar.y":89  */
                                                 { $yyval = self::makePow($yystack->valueAt(1), self::makeSuperscriptInteger($yystack->valueAt(0), $yystack->locationAt(0)), ($yyloc)); };
  break;


  case 21: /* number: "integer"  */
    /* "src/Parser/grammar.y":93  */
                                                { $yyval = self::makeInteger($yystack->valueAt(0), $yystack->locationAt(0)); };
  break;


  case 22: /* number: "decimal number"  */
    /* "src/Parser/grammar.y":94  */
                                                { $yyval = self::makeFloat($yystack->valueAt(0), $yystack->locationAt(0)); };
  break;


  case 23: /* signed_number: number  */
    /* "src/Parser/grammar.y":98  */
                                                { $yyval = $yystack->valueAt(0); };
  break;


  case 24: /* signed_number: "-" number  */
    /* "src/Parser/grammar.y":99  */
                                                { $yyval = self::makeNeg($yystack->valueAt(0), ($yyloc)); };
  break;


  case 25: /* identifier: "identifier"  */
    /* "src/Parser/grammar.y":103  */
                                                { $yyval = self::makeIdentifier($yystack->valueAt(0), $yystack->locationAt(0)); };
  break;



/* "src/Parser/Parser.php":714  */

        default: break;
      }

    $yystack->pop($yylen);
    $yylen = 0;
    /* Shift the result of the reduction.  */
    $yystate = $this->yyLRGotoState($yystack->stateAt(0), $this->yyr1[$yyn]);
    $yystack->push($yystate, $yyval, $yyloc);
    return Parser::YYNEWSTATE;
  }




  /**
   * Parse input from the scanner that was specified at object construction
   * time.  Return whether the end of the input was reached successfully.
   *
   * @return <tt>true</tt> if the parsing succeeds.  Note that this does not
   *          imply that there were no syntax errors.
   */
  public function parse(): bool 

  {



    // Discard the LAC context in case there still is one left from a
    // previous invocation.
    $this->yylacDiscard("init");

    $this->yyerrstatus = 0;
    $this->yynerrs = 0;

    /* Initialize the stack.  */
    $this->yystack->push($this->yystate, $this->yylval, $this->yylloc);



    for (;;)
      switch ($this->label)
      {
        /* New state.  Unlike in the C/C++ skeletons, the state is already
           pushed when we come here.  */
      case Parser::YYNEWSTATE:

        /* Accept?  */
        if ($this->yystate === Parser::YYFINAL) {
          return true;
        }

        /* Take a decision.  First try without lookahead.  */
        $this->yyn = $this->yypact[$this->yystate];
        if ($this->yyPactValueIsDefault($this->yyn))
          {
            $this->label = Parser::YYDEFAULT;
            break;
          }

        /* Read a lookahead token.  */
        if ($this->yychar === Parser::YYEMPTY)
          {

            $this->yychar = $this->yylexer->yylex();
            $this->yylval = $this->yylexer->getLVal();
            $this->yylloc = new Location($this->yylexer->getStartPos(),$this->yylexer->getEndPos());

          }

        /* Convert token to internal form.  */
        $this->yytoken = $this->yytranslate($this->yychar);

        if ($this->yytoken->getCode() === SymbolKind::S_YYerror)
          {
            // The scanner already issued an error message, process directly
            // to error recovery.  But do not keep the error token as
            // lookahead, it is too special and may lead us to an endless
            // loop in error recovery. */
            $this->yychar = LexerInterface::YYUNDEF;
            $this->yytoken = new SymbolKind(SymbolKind::S_YYUNDEF);
            $this->yyerrloc = $this->yylloc;
            $this->label = Parser::YYERRLAB1;
          }
        else
          {
            /* If the proper action on seeing token YYTOKEN is to reduce or to
               detect an error, take that action.  */
            $this->yyn += $this->yytoken->getCode();
            if ($this->yyn < 0 || Parser::YYLAST < $this->yyn || $this->yycheck[$this->yyn] !== $this->yytoken->getCode()) {
              if (!$this->yylacEstablish($this->yystack, $this->yytoken)) {
                $this->label = Parser::YYERRLAB;
              } else
              $this->label = Parser::YYDEFAULT;
            }

            /* <= 0 means reduce or error.  */
            else if (($this->yyn = $this->yytable[$this->yyn]) <= 0)
              {
                if ($this->yyTableValueIsError($this->yyn)) {
                  $this->label = Parser::YYERRLAB;
                } else if (!$this->yylacEstablish($this->yystack, $this->yytoken)) {
                  $this->label = Parser::YYERRLAB;
                } else {
                  $this->yyn = -$this->yyn;
                  $this->label = Parser::YYREDUCE;
                }
              }

            else
              {
                /* Shift the lookahead token.  */
                /* Discard the token being shifted.  */
                $this->yychar = Parser::YYEMPTY;

                /* Count tokens shifted since error; after three, turn off error
                   status.  */
                if ($this->yyerrstatus > 0)
                  --$this->yyerrstatus;

                $this->yystate = $this->yyn;
                $this->yystack->push($this->yystate, $this->yylval, $this->yylloc);
                $this->yylacDiscard("shift");
                $this->label = Parser::YYNEWSTATE;
              }
          }
        break;

      /*-----------------------------------------------------------.
      | yydefault -- do the default action for the current state.  |
      `-----------------------------------------------------------*/
      case Parser::YYDEFAULT:
        $this->yyn = $this->yydefact[$this->yystate];
        if ($this->yyn === 0)
          $this->label = Parser::YYERRLAB;
        else
          $this->label = Parser::YYREDUCE;
        break;

      /*-----------------------------.
      | yyreduce -- Do a reduction.  |
      `-----------------------------*/
      case Parser::YYREDUCE:
        $this->yylen = $this->yyr2[$this->yyn];
        $this->label = $this->yyaction($this->yyn, $this->yystack, $this->yylen);
        $this->yystate = $this->yystack->stateAt(0);
        break;

      /*------------------------------------.
      | yyerrlab -- here on detecting error |
      `------------------------------------*/
      case Parser::YYERRLAB:
        /* If not already recovering from an error, report this error.  */
        if ($this->yyerrstatus === 0)
          {
            ++$this->yynerrs;
            if ($this->yychar === Parser::YYEMPTY) {
              $this->yytoken = null;
            }
            $this->yyreportSyntaxError(new Context($this, $this->yystack, $this->yytoken, $this->yylloc));
          }

        $this->yyerrloc = $this->yylloc;
        if ($this->yyerrstatus === 3)
          {
            /* If just tried and failed to reuse lookahead token after an
               error, discard it.  */

            if ($this->yychar <= LexerInterface::YYEOF)
              {
                /* Return failure if at end of input.  */
                if ($this->yychar === LexerInterface::YYEOF) {
                  return false;
                }
              }
            else
              $this->yychar = Parser::YYEMPTY;
          }

        /* Else will try to reuse lookahead token after shifting the error
           token.  */
        $this->label = Parser::YYERRLAB1;
        break;

      /*-------------------------------------------------.
      | errorlab -- error raised explicitly by YYERROR.  |
      `-------------------------------------------------*/
      case Parser::YYERROR:
        $this->yyerrloc = $this->yystack->locationAt ($this->yylen - 1);
        /* Do not reclaim the symbols of the rule which action triggered
           this YYERROR.  */
        $this->yystack->pop($this->yylen);
        $this->yylen = 0;
        $this->yystate = $this->yystack->stateAt(0);
        $this->label = Parser::YYERRLAB1;
        break;

      /*-------------------------------------------------------------.
      | yyerrlab1 -- common code for both syntax error and YYERROR.  |
      `-------------------------------------------------------------*/
      case Parser::YYERRLAB1:
        $this->yyerrstatus = 3;       /* Each real token shifted decrements this.  */

        // Pop stack until we find a state that shifts the error token.
        for (;;)
          {
            $this->yyn = $this->yypact[$this->yystate];
            if (!$this->yyPactValueIsDefault($this->yyn))
              {
                $this->yyn += SymbolKind::S_YYerror;
                if (0 <= $this->yyn && $this->yyn <= Parser::YYLAST
                    && $this->yycheck[$this->yyn] === SymbolKind::S_YYerror)
                  {
                    $this->yyn = $this->yytable[$this->yyn];
                    if (0 < $this->yyn)
                      break;
                  }
              }

            /* Pop the current state because it cannot handle the
             * error token.  */
            if ($this->yystack->height === 0) {
              return false;
            }


            $this->yyerrloc = $this->yystack->locationAt(0);
            $this->yystack->pop();
            $this->yystate = $this->yystack->stateAt(0);
          }

        if ($this->label === Parser::YYABORT)
          /* Leave the switch.  */
          break;


        /* Muck with the stack to setup for yylloc.  */
        $this->yystack->push (0, null, $this->yylloc);
        $this->yystack->push (0, null, $this->yyerrloc);
        $this->yyloc = $this->yylloc ($this->yystack, 2);
        $this->yystack->pop(2);

        /* Shift the error token.  */
        $this->yylacDiscard("error recovery");

        $this->yystate = $this->yyn;
        $this->yystack->push($this->yyn, $this->yylval, $this->yyloc);
        $this->label = Parser::YYNEWSTATE;
        break;

        /* Accept.  */
      case Parser::YYACCEPT:
        return true;

        /* Abort.  */
      case Parser::YYABORT:
        return false;
      }
}





    /** Check the lookahead yytoken.
     * \returns  true iff the token will be eventually shifted.
     */
    public function yylacCheck(YYStack $yystack, SymbolKind $yytoken): bool
    {
      // Logically, the yylacStack's lifetime is confined to this function.
      // Clear it, to get rid of potential left-overs from previous call.
      $this->yylacStack = [];
      // Reduce until we encounter a shift and thereby accept the token.

      $lacTop = 0;
      while (true)
        {
          $topState = (empty($this->yylacStack)
                          ? $yystack->stateAt($lacTop)
                          : $this->yylacStack[count($this->yylacStack) - 1]);
          $yyrule = $this->yypact[$topState];
          if ($this->yyPactValueIsDefault($yyrule)
              || ($yyrule += $yytoken->getCode()) < 0 || Parser::YYLAST < $yyrule
              || $this->yycheck[$yyrule] !== $yytoken->getCode())
            {
              // Use the default action.
              $yyrule = $this->yydefact[+$topState];
              if ($yyrule === 0) {

                return false;
              }
            }
          else
            {
              // Use the action from yytable.
              $yyrule = $this->yytable[$yyrule];
              if ($this->yyTableValueIsError($yyrule)) {

                return false;
              }
              if (0 < $yyrule) {

                return true;
              }
              $yyrule = -$yyrule;
            }
          // By now we know we have to simulate a reduce.

          // Pop the corresponding number of values from the stack.
          {
            $yylen = $this->yyr2[$yyrule];
            // First pop from the LAC stack as many tokens as possible.
            $lacSize = count($this->yylacStack);
            if ($yylen < $lacSize) {
              for (/* Nothing */; 0 < $yylen; $yylen -= 1) {
                array_pop($this->yylacStack);
              }
              $yylen = 0;
            } else if ($lacSize !== 0) {
              $this->yylacStack = [];
              $yylen -= $lacSize;
            }
            // Only afterwards look at the main stack.
            // We simulate popping elements by incrementing lacTop.
            $lacTop += $yylen;
          }
          // Keep topState in sync with the updated stack.
          $topState = (empty($this->yylacStack)
                      ? $yystack->stateAt($lacTop)
                      : $this->yylacStack[count($this->yylacStack) - 1]);
          // Push the resulting state of the reduction.
          $state = $this->yyLRGotoState($topState, $this->yyr1[$yyrule]);

          $this->yylacStack[] = $state;
        }
    }

    /** Establish the initial context if no initial context currently exists.
     * \returns  true iff the token will be eventually shifted.
     */
    public function yylacEstablish(YYStack $yystack, SymbolKind $yytoken): bool {
      /* Establish the initial context for the current lookahead if no initial
         context is currently established.

         We define a context as a snapshot of the parser stacks.  We define
         the initial context for a lookahead as the context in which the
         parser initially examines that lookahead in order to select a
         syntactic action.  Thus, if the lookahead eventually proves
         syntactically unacceptable (possibly in a later context reached via a
         series of reductions), the initial context can be used to determine
         the exact set of tokens that would be syntactically acceptable in the
         lookahead's place.  Moreover, it is the context after which any
         further semantic actions would be erroneous because they would be
         determined by a syntactically unacceptable token.

         yylacEstablish should be invoked when a reduction is about to be
         performed in an inconsistent state (which, for the purposes of LAC,
         includes consistent states that don't know they're consistent because
         their default reductions have been disabled).

         For parse.lac=full, the implementation of yylacEstablish is as
         follows.  If no initial context is currently established for the
         current lookahead, then check if that lookahead can eventually be
         shifted if syntactic actions continue from the current context.  */
      if ($this->yylacEstablished) {
        return true;
      } else {

        $this->yylacEstablished = true;
        return $this->yylacCheck($yystack, $yytoken);
      }
    }

    /** Discard any previous initial lookahead context because of event.
     * \param event  the event which caused the lookahead to be discarded.
     *               Only used for debbuging output.  */
    public function yylacDiscard(string $event): void {
     /* Discard any previous initial lookahead context because of Event,
        which may be a lookahead change or an invalidation of the currently
        established initial context for the current lookahead.

        The most common example of a lookahead change is a shift.  An example
        of both cases is syntax error recovery.  That is, a syntax error
        occurs when the lookahead is syntactically erroneous for the
        currently established initial context, so error recovery manipulates
        the parser stacks to try to find a new initial context in which the
        current lookahead is syntactically acceptable.  If it fails to find
        such a context, it discards the lookahead.  */
      if ($this->yylacEstablished) {

        $this->yylacEstablished = false;
      }
    }

    /** The stack for LAC.
     * Logically, the yylacStack's lifetime is confined to the function
     * yylacCheck. We just store it as a member of this class to hold
     * on to the memory and to avoid frequent reallocations.
     * @var int[]
     */
    private array $yylacStack = [];
    /**  Whether an initial LAC context was established. */
    public bool $yylacEstablished = false;



  /**
   * @param SymbolKind[] $yyarg
   */
  private function yysyntaxErrorArguments(Context $yyctx, array &$yyarg, int $yyargn): int {
    /* There are many possibilities here to consider:
       - If this state is a consistent state with a default action,
         then the only way this function was invoked is if the
         default action is an error action.  In that case, don't
         check for expected tokens because there are none.
       - The only way there can be no lookahead present (in tok) is
         if this state is a consistent state with a default action.
         Thus, detecting the absence of a lookahead is sufficient to
         determine that there is no unexpected or expected token to
         report.  In that case, just report a simple "syntax error".
       - Don't assume there isn't a lookahead just because this
         state is a consistent state with a default action.  There
         might have been a previous inconsistent state, consistent
         state with a non-default action, or user semantic action
         that manipulated yychar.  (However, yychar is currently out
         of scope during semantic actions.)
       - Of course, the expected token list depends on states to
         have correct lookahead information, and it depends on the
         parser not to perform extra reductions after fetching a
         lookahead from the scanner and before detecting a syntax
         error.  Thus, state merging (from LALR or IELR) and default
         reductions corrupt the expected token list.  However, the
         list is correct for canonical LR with one exception: it
         will still contain any token that will not be accepted due
         to an error action in a later state.
    */
    $yycount = 0;
    if ($yyctx->getToken() !== null) {
        if ($yyarg !== null) {
          $yyarg[$yycount] = $yyctx->getToken();
        }

        $yycount += 1;
        $yycount += $yyctx->getExpectedTokens($yyarg, 1, $yyargn);
    }

    return $yycount;
  }


  /**
   * Build and emit a "syntax error" message in a user-defined way.
   *
   * @param ctx  The context of the error.
   */
  private function yyreportSyntaxError(Context $yyctx): void {
      $message = "syntax error";
      if ($this->yyErrorVerbose) {
          /** @var SymbolKind[] $yyarg */
          $yyarg = [];
          $yycount = $this->yysyntaxErrorArguments($yyctx, $yyarg, 5);
          $yystr = [];
          for ($yyi = 0; $yyi < $yycount; ++$yyi) {
              $yystr[$yyi] = $yyarg[$yyi]->getName();
          }
          if ($yycount > 1) {
                $unexpected = array_shift($yystr);
                $message .= sprintf(", got %s, but expecting %s", $unexpected, implode(' or ', $yystr));
            } else if ($yycount > 0) {
                $message = sprintf("syntax error, unexpected '%s'", $yystr[0]);
            }
      }
      $this->yyerror($yyctx->getLocation(), $message);
      
  }

  /**
   * Whether the given <code>yypact_</code> value indicates a defaulted state.
   * @param yyvalue   the value to check
   */
  public function yyPactValueIsDefault(int $yyvalue): bool {
    return $yyvalue === $this->yypact_ninf;
  }

  /**
   * Whether the given <code>yytable_</code>
   * value indicates a syntax error.
   * @param yyvalue the value to check
   */
  public function yyTableValueIsError(int $yyvalue): bool {
    return $yyvalue === $this->yytable_ninf;
  }

  public int $yypact_ninf = -24;
  public int $yytable_ninf = -1;

/* YYPACT[STATE-NUM] -- Index in YYTABLE of the portion describing
   STATE-NUM.  */
  
  /** @var int[] */
  public array $yypact = array(    20,   -24,   -24,    20,   -24,    20,     9,   -24,     3,    -1,
     -24,   -24,     0,   -24,    19,   -24,     1,   -24,    20,    20,
      20,    20,    20,   -24,   -24,    20,    21,   -24,    -1,    -1,
     -24,   -24,   -24,   -24,    22,   -24,   -24,   -24);
  

/* YYDEFACT[STATE-NUM] -- Default reduction number in state STATE-NUM.
   Performed when YYTABLE does not specify something else to do.  Zero
   means the default is an error.  */
  
  /** @var int[] */
  public array $yydefact = array(     0,    21,    22,     0,    25,     0,     0,     2,     3,     4,
       7,    12,    14,    16,    17,    13,     0,     1,     0,     0,
       0,     0,     0,     8,    20,     0,     0,    19,     6,     5,
       9,    10,    11,    15,     0,    23,    18,    24);
  

/* YYPGOTO[NTERM-NUM].  */
  
  /** @var int[] */
  public array $yypgoto = array(   -24,   -24,    31,   -24,    11,    -3,    -8,   -24,   -23,   -24,
     -24);
  

/* YYDEFGOTO[NTERM-NUM].  */
  
  /** @var int[] */
  public array $yydefgoto = array(     0,     6,     7,     8,     9,    10,    11,    12,    13,    36,
      14);
  

/* YYTABLE[YYPACT[STATE-NUM]] -- What to do in state STATE-NUM.  If
   positive, shift that token.  If negative, reduce the rule whose
   number is the opposite.  If YYTABLE_NINF, syntax error.  */
  
  /** @var int[] */
  public array $yytable = array(    15,    23,     1,    35,    24,     2,    20,    21,    22,    17,
      25,    37,     4,     5,    18,    19,    27,    30,    31,    32,
      23,    23,    33,     1,     1,     1,     2,     2,     2,    28,
      29,     3,    34,     4,     5,    26,    16);
  


  /** @var int[] */
  public array $yycheck = array(     3,     9,     3,    26,     4,     6,     7,     8,     9,     0,
      10,    34,    13,    14,    11,    12,    15,    20,    21,    22,
      28,    29,    25,     3,     3,     3,     6,     6,     6,    18,
      19,    11,    11,    13,    14,    16,     5);
  

/* YYSTOS[STATE-NUM] -- The symbol kind of the accessing symbol of
   state STATE-NUM.  */
  
  /** @var int[] */
  public array $yystos = array(     0,     3,     6,    11,    13,    14,    19,    20,    21,    22,
      23,    24,    25,    26,    28,    23,    20,     0,    11,    12,
       7,     8,     9,    24,     4,    10,    16,    15,    22,    22,
      23,    23,    23,    23,    11,    26,    27,    26);
  

/* YYR1[RULE-NUM] -- Symbol kind of the left-hand side of rule RULE-NUM.  */
  
  /** @var int[] */
  public array $yyr1 = array(     0,    18,    19,    20,    21,    21,    21,    22,    22,    22,
      22,    22,    23,    23,    24,    24,    25,    25,    25,    25,
      25,    26,    26,    27,    27,    28);
  

/* YYR2[RULE-NUM] -- Number of symbols on the right-hand side of rule RULE-NUM.  */
  
  /** @var int[] */
  public array $yyr2 = array(     0,     2,     1,     1,     1,     3,     3,     1,     2,     3,
       3,     3,     1,     2,     1,     3,     1,     1,     3,     3,
       2,     1,     1,     1,     2,     1);
  




  /* YYTRANSLATE(TOKEN-NUM) -- Symbol number corresponding to TOKEN-NUM
     as returned by yylex, with out-of-bounds checking.  */
  private function yytranslate(int $t): SymbolKind
  {
    // Last valid token kind.
    $code_max = 272;
    if ($t <= 0)
      return new SymbolKind(SymbolKind::S_YYEOF);
    else if ($t <= $code_max)
      return new SymbolKind($this->yytranslate_table[$t]);
    else
      return new SymbolKind(SymbolKind::S_YYUNDEF);
  }
  
  /** @var int[] */
  public array $yytranslate_table = array(     0,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     2,     2,     2,     2,
       2,     2,     2,     2,     2,     2,     1,     2,     3,     4,
       5,     6,     7,     8,     9,    10,    11,    12,    13,    14,
      15,    16,    17);
  


  public const YYLAST = 36;
  public const YYEMPTY = -2;
  public const YYFINAL = 17;
  public const YYNTOKENS = 18;


}
