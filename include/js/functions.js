/* Functions.js
 * This is a collection of handy JavaScript functions. 
 */

function $(name)
{
	if(!eval(document.getElementById(name)))
		alert('Unknown element');

	return document.getElementById(name);
}

function st(name)
{
	return $(name).value;
}

function nu(name)
{
	return $(name).value * 1;
}

function isNumeric(value) 
{
	return typeof value != "boolean" && value !== null && !isNaN(+ value);
}
