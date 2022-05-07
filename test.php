<?php

//    echo ceil(65000/30000);

//$a = 3;
//
//echo "$a \n";
//
//echo '$a'."\n";
//
//echo "$a"."$a \n";
//
//echo "\\\$a \n";
//
//echo "$a"+"$a"." \n";

//$string =
//    "Lorem ipsum dolor sit amet, consectetur
//adipiscing elit. Nunc ut elit id mi ultricies
//adipiscing. Nulla facilisi. Praesent pulvinar,
//sapien vel feugiat vestibulum, nulla dui pretium orci,
//non ultricies elit lacus quis ante. Lorem ipsum dolor
//sit amet, consectetur adipiscing elit. Aliquam
//pretium ullamcorper urna quis iaculis. Etiam ac massa
//sed turpis tempor luctus. Curabitur sed nibh eu elit
//mollis congue. Praesent ipsum diam, consectetur vitae
//ornare a, aliquam a nunc. In id magna pellentesque
//tellus posuere adipiscing. Sed non mi metus, at lacinia
//augue. Sed magna nisi, ornare in mollis in, mollis
//sed nunc. Etiam at justo in leo congue mollis.
//Nullam in neque eget metus hendrerit scelerisque
//eu non enim. Ut malesuada lacus eu nulla bibendum
//id euismod urna sodales. ";
//
//$compressed = gzcompress($string);
//
//echo gzuncompress($compressed);

//echo substr("Hello world",-2);

//$str = "Hello world";
//
//echo $str[-2];

trait HelloWorld {
    public function sayHello()
    {
        echo 'hello world';
    }
}

trait HiMan {
    public function sayHello()
    {
        echo 'hi man';
    }
}

class TestSay {
    use HelloWorld, HiMan;
}

$tsObj = new TestSay();

$tsObj->sayHello();