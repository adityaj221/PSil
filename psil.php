<?php
error_reporting(E_ERROR | E_PARSE);
$input_file = "input.txt";
$file = file_get_contents($input_file, true);
$file = preg_replace("/\n/", " ", $file);

$psil = new PSil;
$value = $psil -> compute_exp($file);
echo $value;

/* Class to store the static contents of the code.
 * These can be modified as per the requirement and can also allow to extend the feature of the program.
 */
Class Util { 
	function __construct() { } 

	// List of allowed amthematical symbols.
	// Can support more symbols if required.
	public $allowed_symbols = array("+", "-", "*", "/");
	// List of allowed special keywords.
	// Can support more keywords if required.
	public $allowed_keywords = array("bind");

	// Regex strings to verify different components of the expression. 
	// Can be optimised and altered later according to the chages added to the design.
	public $check_number_string = "/(-)?(\d+)/";
	public $check_alphabet_string = "/^[A-Za-z]+$/";
	public $preg_match_all_bracket_string = "/(?=\(((?:[^()]++|\((?1)\))++)\))/";
	public $preg_match_bracket_string = "/(?<=\()[^\(.]+?(?<=\))/"; 
	public $eval_expression = "/(\-)?\d+ (\+|\-|\*|\/) (\-)?\d+/";

	// Array to store all the associated data.
	public $binding_array = array();
}

/* Class which parses the Psil expressions to return the result.
 * This class can check if the expression is valid, perform the requisite operation on the expression, etc.
 */
Class PSil{

	private $exp;
	function __construct() { 
		$this->util = new Util();
		$this->operation = new Operation();
	} 

	// Function which takes the expression as input and returns the result of the expression.
	// If the expression is incorrect or if the program has error, it retrns the error message.
	public function compute_exp($expression){
		$this->exp = $expression;
		if($this->check_exp()){
			$val = $this->parse_exp();
			if($val !== false && !error_get_last()){
				return $val;
			}
		}
		return "Invalid program";
	}

	/* This function validates the expression.
	 * Empty expression brackets are not allowed in this.
	 */
	private function check_exp(){
		// Check if the brackets don't mismatch.
		if(substr_count($this->exp,'(') != substr_count($this->exp,')')){
			return false;
		}
		// Validate if the string consists of bracketed expressions and check for each such bracketed expression(in case of nesting).
		if(preg_match_all($this->util->preg_match_all_bracket_string, $this->exp, $match_result)){
			foreach ($match_result[1] as $key) {
				$key = preg_split("/\s+/", trim($key));
				if(count($key == 1) && preg_match($this->util->check_number_string, $this->exp)){
					$this->exp = preg_replace('/\(' . preg_quote($key[0], '/') . '\)/', $key[0], $this->exp);
					continue;
				}
				// Check if expression has less than 3 parts(as it is the minimum size of an required expression),
				// or the first part of the expression falls under any of the allowed symbols or keywords.
				else if(count($key) < 3 || !$this->check_symbol($key[0])){
					return false;
				}
				// Check if the 1st part of the expression is in the list of allowed keywords and the second part is alphabatic
				if(in_array($key[0], $this->util->allowed_keywords) && !preg_match($this->util->check_alphabet_string, $key[1])){
					return false;
				}
			}
			return true;
		}

		// if the string doesn't contains bracketed text, then check if the string consists of numbers only.
		else if(preg_match($this->util->check_number_string, $this->exp)){
			return true;	
		}
		return false;
	}

	// Function to verify if the provided symbol falls under any of the allowed symbols or keywords.
	private function check_symbol($type){
		if(in_array($type, $this->util->allowed_symbols) || in_array($type, $this->util->allowed_keywords)){
			return true;
		}
		return false;
	} 

	// Funcion to parse the expression into smaller chunks of individual brackets and evaluate then iteratively to evaluate the entire expression as a whole.
	private function parse_exp(){
		// Get all the bracketed expression including the nested expressions.
		preg_match_all($this->util->preg_match_all_bracket_string, $this->exp, $match);
		$temp_arr = array();
		$cleaned_exp = $match[1];
		$result = count($cleaned_exp) ? 0 : $this->exp;
		// Loop through all the bracketed expression.
		foreach ($cleaned_exp as $my_exp) {
			// Check if there is an expression being repeated. This will reduce the processing of same expression multiple times.
			if(!array_key_exists($my_exp, $this->util->binding_array)){
				// Insert an empty expression inside an asociative array, This will help in replacing its value in the main expression.
				$this->util->binding_array[$my_exp] = "";
				array_push($temp_arr, $my_exp);
			}
		}
		// Loop through all the expression once again, this time processing each one of them. 
		// If an expression consists of bracketed text check if the bracketed expression is processed, 
		// If yes, replace the bracketed expression with its value,
		// then add it to the back of the array and move to the next expression. 
		// If expression has no bracketed sub expression, evaluate it.
		while($temp_arr){
			$arr_val = array_shift($temp_arr);
			// Check if expression consists of bracketed sub expressions.
			if(preg_match($this->util->preg_match_bracket_string, $arr_val)){
				// Get tha list of all such sub expressions.
				preg_match_all($this->util->preg_match_all_bracket_string, $arr_val, $my_match);
				// Exclude the parent expression from the list
				if(count($my_match[1] > 1)){
					// Loop through each sub-expression
					foreach ($my_match[1] as $new_val) {
						// Check if the sub-expression exists in the associative array and if its value is not null.
						if(array_key_exists($new_val, $this->util->binding_array) && $this->util->binding_array[$new_val] != ""){
							// Replace the sub-expression with its value from the array.
							$sub_exp = preg_split("/\s+/", trim($new_val));
							// Check if mathematical symbol.
							if(in_array($sub_exp[0], $this->util->allowed_symbols)){
								$arr_val = preg_replace('/\(\\' . $new_val . '\)/', $this->util->binding_array[$new_val], $arr_val);
							}
							// Check if allowed keyword.
							else if(in_array($sub_exp[0], $this->util->allowed_keywords)){
								$arr_val = preg_replace('/\(' . $new_val . '\)/', $this->util->binding_array[$new_val], $arr_val);
							}
							else{
								return false;
							}
						}
					}
				}
				// Push the expression to the end of the array and parse it again in the next iteration.
				$temp_arr[] = $arr_val;
				continue;
			}
			// If the expression doesn't consists of the sub-expression, Split the expression.
			$exp = preg_split("/\s+/", trim($arr_val));
			// Execute the operation to be performed on the expression.
			// Store the result in the associative array against the expression as the key.
			// This will enable to replace the subexpression with its value in the parent expression,if any.
			$this->util->binding_array[$arr_val] = $this->execute_operation($exp[0], $exp);
			// Update the result variable.
			$result = $this->util->binding_array[$arr_val];
		}
		return $result;
	}
	//  Function to execute the appropriate operation on the expression.
	private function execute_operation($operation, $exp){
		// If the operation matches with any of the allowed arithematic symbols in the array, 
		// function call is made to perform the arithematic operation.
		if(in_array($operation, $this->util->allowed_symbols)){
			return $this->operation->perform_arithematic($exp);	
		}
		// If the operation matches with any of the allowed keywords in the array,
		// function is called with the name matching that keyword.
		// This allows extensibility to the code to add new keywords and their corresponding function calls.
		if(in_array($operation, $this->util->allowed_keywords)){
			return $this->operation->{$operation}($exp);
		}
	}
}

/* Class to perform the allowed operations on the expression. 
 * New functions can be added to this class or this class can be extended to modify the operation being performed on an expression.
 */
Class Operation{
	function __construct() { 
		$this->util = new Util();
	}

	// Function performns the bind operation. It stores the value of the keyword in an associative array.
	// This value can be reused in further calculations.
	public function bind($exp){
		// Check if the number of parts in the expression are less or greater than 3(The specified length for the keyword's expression)
		if(count($exp) != 3){
			return false;
		}
		// Check if the second part of the expression is a alphabetical value. 
		if(preg_match($this->util->check_alphabet_string, $exp[2])){
			// If the same exists in the associative array. Assign the value 
			if(array_key_exists($exp[2], $this->util->binding_array)){
				$this->util->binding_array[$exp[1]] = $this->util->binding_array[$exp[2]];
				return $this->util->binding_array[$exp[1]];
			}
			else{
				return false;
			}
		}
		// Assign the expressions second part's value to the first part.
		$this->util->binding_array[$exp[1]] = $exp[2];
		return $this->util->binding_array[$exp[1]];
	}

	// Function performns the arithematic operations. It calculates the value of the expression.
	// This function uses the php eval() inbuilt function to calculate the expression's value.
	// This is done to avoid additional code to verify the symbol and apply the operation.
	// Extending the class can allow to perform different custom arithematic operation as required.
	public function perform_arithematic($exp){
		// Since the operations are binary, setting the first 2 values to the variables.
		$my_operation = $exp[0];
		$result = $this->set_val($exp[1], $this->util->binding_array);
		if(!$result){
			return false;
		}
		$count = 0;
		// Iterate through each expression part and apply the operation to it.
		foreach ($exp as $key) {
			$count++;
			// Skip the first 2 parts as they ave already been assigned.
			if($count <= 2){
				continue;
			}
			$key = $this->set_val($key, $this->util->binding_array);
			if($key){
				// Prepare the eval string using the operation and the operands
				$eval_string = "$result $my_operation $key";
				// Safety check for the eval function to verify the format of the eval string
				if(preg_match($this->util->eval_expression, $eval_string)){
					$result = eval("return $eval_string;");
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}
		return $result;
	}

	// This function checks the operand value in the associative array and also verifies the same against the regex string.
	private function set_val($key, $binding_array){
		// Check if the key is a numeric
		if(preg_match($this->util->check_number_string, $key)){
			return $key;
		} 
		// Check if the key is an expression.
		else if(preg_match($this->util->preg_match_bracket_string, $key)){
			$key = parse_exp($exp);
		}
		// Check if the key exists in the associative array.
		else if(preg_match($this->util->check_alphabet_string,$key) && array_key_exists($key, $binding_array)){
			$key = $binding_array[$key];
		}
		else{
			return false;
		}
		return $key;
	}
}
?>