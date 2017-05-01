<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 5/1/2017
 * Time: 08:19
 */

require_once __DIR__ . "/../vendor/autoload.php";

function showError($message)
{
	echo $message . PHP_EOL;
	echo "\t1:Script name" . PHP_EOL;
	echo "\t2:PhpJs source root dir" . PHP_EOL;
	echo "\t3:JS export dir. can be omitted" . PHP_EOL;
	exit(1);
}

if ($argc!=4){
	showError("Bad argument count");
}

$scriptName = $argv[1];
$scriptDir = getcwd();
$phpRootDir = $argv[2];
$jsRootDir = $argv[3];
$phpFilename = $scriptDir . DIRECTORY_SEPARATOR . $scriptName;

if (!file_exists($phpFilename)){
	showError("Source script not exist");
}
if (!file_exists($jsRootDir)){
	showError("PHP root dir not exist");
}
if (!file_exists($jsRootDir)){
	showError("JS root dir not exist");
}

$phpContent = file_get_contents($scriptDir.DIRECTORY_SEPARATOR.$scriptName);
$errorCount=0;
try{
	$parser = (new \PhpParser\ParserFactory())->create(\PhpParser\ParserFactory::PREFER_PHP7);
	$jsPrinter = new \phptojs\JsPrinter\JsPrinter();

	$stmts = $parser->parse($phpContent);
	ob_start();
	$jsCode = $jsPrinter->jsPrint($stmts);
	$errors = ob_get_clean();
	$errors = explode(PHP_EOL, $errors);
	foreach ($errors as $error) {
		if ($error != "") {
			$errorCount++;
			echo "Warning: " . $error.PHP_EOL;
		}
	}
	foreach ($jsPrinter->getErrors() as $error) {
		$errorCount++;
		echo "Warning: " . $error . PHP_EOL;
	}
	$dotPos = strrpos($phpFilename,".");
	$phpFilenameWithoutExtension = substr($phpFilename,0,$dotPos);
	$filePathWithoutExtension = substr($phpFilenameWithoutExtension,strlen($phpRootDir));
	if (in_array(substr($filePathWithoutExtension,0,1),["/","\\"])){
		$filePathWithoutExtension = substr($filePathWithoutExtension,1);
	}
	$jsFileName = realpath($jsRootDir).DIRECTORY_SEPARATOR.$filePathWithoutExtension.".js";
	$jsCode = "/**
 * File generated by PHP to JS converter
 * Don't modify this file because changes will be lost 
 */
 ".$jsCode;
	file_put_contents($jsFileName, $jsCode);
} catch (PhpParser\Error $e) {
	echo 'ERROR:', $e->getMessage();
	$errorCount++;
} catch (Exception $e) {
	echo "ERROR:Some is wrong".PHP_EOL.$e->getMessage();
	$errorCount++;
}
if ($errorCount>0){
	exit(1);
}