<?php

function userfeedback_check_logic( $symbol, $value, $compare_to ) {
	switch ( $symbol ) {
		case '=':
			return strval( $value ) === strval( $compare_to );
		case '!=':
			return strval( $value ) !== strval( $compare_to );
		case '<':
			return intval( $value ) < intval( $compare_to );
		case '>':
			return intval( $value ) > intval( $compare_to );
		case 'in':
			return is_array( $value ) && in_array( $compare_to, $value );
		default:
			return false;
	}
}
