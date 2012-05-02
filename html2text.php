
<!-- saved from url=(0099)http://iaml.googlecode.com/svn/trunk/org.openiaml.model.runtime/src/include/html2text/html2text.php -->
<html><head><meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"><style type="text/css"></style></head><body><pre style="word-wrap: break-word; white-space: pre-wrap;">&lt;?php
/******************************************************************************
 * Copyright (c) 2010 Jevon Wright and others.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Jevon Wright - initial API and implementation
 ****************************************************************************/

/**
 * Tries to convert the given HTML into a plain text format - best suited for
 * e-mail display, etc.
 *
 * &lt;p&gt;In particular, it tries to maintain the following features:
 * &lt;ul&gt;
 *   &lt;li&gt;Links are maintained, with the 'href' copied over
 *   &lt;li&gt;Information in the &amp;lt;head&amp;gt; is lost
 * &lt;/ul&gt;
 *
 * @param html the input HTML
 * @return the HTML converted, as best as possible, to text
 */
function convert_html_to_text($html) {
	$html = fix_newlines($html);

	$doc = new DOMDocument();
	if (!$doc-&gt;loadHTML($html))
		throw new Html2TextException("Could not load HTML - badly formed?", $html);

	$output = iterate_over_node($doc);

	// remove leading and trailing spaces on each line
	$output = preg_replace("/[ \t]*\n[ \t]*/im", "\n", $output);

	// remove leading and trailing whitespace
	$output = trim($output);

	return $output;
}

/**
 * Unify newlines; in particular, \r\n becomes \n, and
 * then \r becomes \n. This means that all newlines (Unix, Windows, Mac)
 * all become \ns.
 *
 * @param text text with any number of \r, \r\n and \n combinations
 * @return the fixed text
 */
function fix_newlines($text) {
	// replace \r\n to \n
	$text = str_replace("\r\n", "\n", $text);
	// remove \rs
	$text = str_replace("\r", "\n", $text);

	return $text;
}

function next_child_name($node) {
	// get the next child
	$nextNode = $node-&gt;nextSibling;
	while ($nextNode != null) {
		if ($nextNode instanceof DOMElement) {
			break;
		}
		$nextNode = $nextNode-&gt;nextSibling;
	}
	$nextName = null;
	if ($nextNode instanceof DOMElement &amp;&amp; $nextNode != null) {
		$nextName = strtolower($nextNode-&gt;nodeName);
	}

	return $nextName;
}
function prev_child_name($node) {
	// get the previous child
	$nextNode = $node-&gt;previousSibling;
	while ($nextNode != null) {
		if ($nextNode instanceof DOMElement) {
			break;
		}
		$nextNode = $nextNode-&gt;previousSibling;
	}
	$nextName = null;
	if ($nextNode instanceof DOMElement &amp;&amp; $nextNode != null) {
		$nextName = strtolower($nextNode-&gt;nodeName);
	}

	return $nextName;
}

function iterate_over_node($node) {
	if ($node instanceof DOMText) {
		return preg_replace("/\\s+/im", " ", $node-&gt;wholeText);
	}
	if ($node instanceof DOMDocumentType) {
		// ignore
		return "";
	}

	$nextName = next_child_name($node);
	$prevName = prev_child_name($node);

	$name = strtolower($node-&gt;nodeName);

	// start whitespace
	switch ($name) {
		case "hr":
			return "------\n";

		case "style":
		case "head":
		case "title":
		case "meta":
		case "script":
			// ignore these tags
			return "";

		case "h1":
		case "h2":
		case "h3":
		case "h4":
		case "h5":
		case "h6":
			// add two newlines
			$output = "\n";
			break;

		case "p":
		case "div":
			// add one line
			$output = "\n";
			break;

		default:
			// print out contents of unknown tags
			$output = "";
			break;
	}

	// debug
	//$output .= "[$name,$nextName]";

	for ($i = 0; $i &lt; $node-&gt;childNodes-&gt;length; $i++) {
		$n = $node-&gt;childNodes-&gt;item($i);

		$text = iterate_over_node($n);

		$output .= $text;
	}

	// end whitespace
	switch ($name) {
		case "style":
		case "head":
		case "title":
		case "meta":
		case "script":
			// ignore these tags
			return "";

		case "h1":
		case "h2":
		case "h3":
		case "h4":
		case "h5":
		case "h6":
			$output .= "\n";
			break;

		case "p":
		case "br":
			// add one line
			if ($nextName != "div")
				$output .= "\n";
			break;

		case "div":
			// add one line only if the next child isn't a div
			if ($nextName != "div" &amp;&amp; $nextName != null)
				$output .= "\n";
			break;

		case "a":
			// links are returned in [text](link) format
			$href = $node-&gt;getAttribute("href");
			if ($href == null) {
				// it doesn't link anywhere
				if ($node-&gt;getAttribute("name") != null) {
					$output = "[$output]";
				}
			} else {
				if ($href == $output) {
					// link to the same address: just use link
					$output;
				} else {
					// replace it
					$output = "[$output]($href)";
				}
			}

			// does the next node require additional whitespace?
			switch ($nextName) {
				case "h1": case "h2": case "h3": case "h4": case "h5": case "h6":
					$output .= "\n";
					break;
			}

		default:
			// do nothing
	}

	return $output;
}

class Html2TextException extends Exception {
	var $more_info;

	public function __construct($message = "", $more_info = "") {
		parent::__construct($message);
		$this-&gt;more_info = $more_info;
	}
}
</pre></body></html>