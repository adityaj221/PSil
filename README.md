Here’s the specification for the Psil (Pronounced Sil) language

Psil is an expression oriented language. Expressions evaluate to values.
In this section we will define what valid expressions are, in the next section, we will
specify how valid expressions are to be evaluated.
● A Psil expression can be one of:
	● an atom
	● a s-expression
● An atom can be one of:
	● a Number
	● a variable
		■ Variables are combinations of the letters az,
AZ.(e.g. foo)
		■ No other characters are allowed. (e.g _bar is not valid)
	● a symbol. Symbols are one of:
		■ +, *, -, /
		■ bind
● An s-expression
is one or more expressions inside parentheses:
	● e.g (1 2 3)
	● e.g (+ 1 3)
	● e.g. (bind x 42)

Evaluating Psil expressions

Expressions evaluate to values. Here are the rules to evaluate expressions:
● Atoms
	● Numbers evaluate to themselves
		■ e.g. 5 evaluates to 5
	● Variables evaluate to their “value” in the current environment
		■ Variables can be bound to expressions via the `bind` special symbol.
		■ The value of a variable is the value of the expression it is bound to
		■ For example this expression (bind x 42) evaluates to 42 and creates abinding for the variable x to 42. If someone subsequently references x, x will evaluate to 42.
		■ More on bind in the next section
● S-expressions
	● The first expression in the sexpression
must be a symbol
	● The subsequent expressions are each evaluated and act as inputs to
the symbol
	● Rules for evaluating symbols are as follows:
		■ The symbols +, * represent the arithmetic functions addition and multiplication respectively.
	● (+ 1 2) => 3
	● (* 2 3) => 6
	● (+ 1 (* 2 3)) => 7
	● you can think of this as a prefix notation for arithmetic, i.e. instead of writing 1 + 2 , in Psil we write (+ 1 2)
		■ The symbol bind is a special symbol. Evaluating a bind expression does two things:
			● the second argument to bind is itself an expression, that is evaluated
			● the first argument to bind is the name of a variable
			● bind creates a binding for the variable in the current environment and returns the value that the variable was bound to
				○ Examples:
					■ (bind x 42) => 42
					■ (bind foo (+ 1 2 3 4)) => 10
			● Variables that are thus bound can be used in subsequent expressions
				○ Examples:
					■ (bind x 42) (+ x 10) => 52
					■ (bind foo 10) (* foo 20) => 200
			● Unbound variables are meaningless and your interpreter should raise an error if it encounters one.

Evaluating Psil programs

● Psil programs are collections of expressions.
● To evaluate the program:
	● Start with an empty environment
	● Evaluate each expression till the end of file
	● Return the result of the last expression in the program
	● Note : Variables can only be used if they have been previously bound

This program is expected to:
1. Read a multiline input.
2. Evaluate it.
3. Print the result of evaluating the last expression or print “Invalid program” if there is an error in the program.
4. End