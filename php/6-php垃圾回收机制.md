### 垃圾回收基本概念
PHP语言同其他语言一样，具有垃圾回收机制。那么今天我们要为大家讲解的内容就是关于PHP垃圾回收机制的相关问题。希望对大家有所帮助。

PHP strtotime应用经验之谈PHP memory_get_usage()管理内存PHP unset全局变量运用问题详解PHP  unset()函数销毁变量教你快速实现PHP全站权限验证一、PHP 垃圾回收机制(Garbage Collector 简称GC)  在PHP中，没有任何变量指向这个对象时，这个对象就成为垃圾。PHP会将其在内存中销毁；这是PHP的GC垃圾处理机制，防止内存溢出。当一个PHP线程结束时，当前占用的所有内存空间都会被销毁，当前程序中所有对象同时被销毁。GC进程一般都跟着每起一个SESSION而开始运行的.gc目的是为了在session文件过期以后自动销毁删除这些文件.二、__destruct  /unset __destruct() 析构函数，是在垃圾对象被回收时执行。

unset 销毁的是指向对象的变量，而不是这个对象。三、 Session  与PHP垃圾回收机制由于PHP的工作机制，它并没有一个daemon线程来定期的扫描Session信息并判断其是否失效，当一个有效的请求发生时，PHP  会根据全局变量 session.gc_probability和session.gc_divisor的值，来决定是否启用一个GC。  在默认情况下，session.gc_probability=1, session.gc_divisor  =100也就是说有1%的可能性启动GC(也就是说100个请求中只有一个gc会伴随100个中的某个请求而启动).

PHP垃圾回收机制的工作就是扫描所有的Session信息，用当前时间减去session***修改的时间，同session.gc_maxlifetime参数进行比较，如果生存时间超过gc_maxlifetime(默认24分钟),就将该session删除。

但是，如果你Web服务器有多个站点，多个站点时,GC处理session可能会出现意想不到的结果，原因就是：GC在工作时，并不会区分不同站点的session.那么这个时候怎么解决呢？

修改session.save_path,或使用session_save_path()让每个站点的session保存到一个专用目录，
提供GC的启动率，自然，PHP垃圾回收机制的启动率提高，系统的性能也会相应减低，不推荐。
在代码中判断当前session的生存时间，利用session_destroy()删除。

### 引用计数基本知识

每个php变量存在一个叫做”zval”的变量容器中.一个zval变量容器,除了包含变量的类型和值,还包括两个字节的额外信息.

***个是”is_ref”,是个bool值,用来标识这个变量是否是属于引用集合(reference  set).通过这个字节,php引擎才能把普通变量和引用变量区分开.由于php允许用户通过使用&来使用自定义引用,zval变量容器中还有一个内部引用计数机制,来优化内存使用.第二个额外字节是”refcount”,用来表示指向这个zval变量容器的变量(也称符号即symbol)个数.

当一个变量被赋常量值时,就会生成一个zval变量容器,如下例所示:

<?php    $a = "new string";    ?>
在上例中,新的变量是a,是在当前作用域中生成的.并且生成了类型为string和值为”new  string”的变量容器.在额外的两个字节信息中,”is_ref”被默认设置为false,因为没有任何自定义的引用生成.”refcount”被设定为1,因为这里只有一个变量使用这个变量容器.调用xdebug查看一下变量内容:

```php

<?php    $a = "new string";    xdebug_debug_zval('a');    ?>

```
以上代码会输出:

a: (refcount=1, is_ref=0)='new string'
对变量a增加一个引用计数

<?php    $a = "new string";    $b = $a;    xdebug_debug_zval('a');    ?>
以上代码会输出:

a: (refcount=2, is_ref=0)='new string'
这时,引用次数是2,因为同一变量容器被变量a和变量b关联.当没必要时,php不会去复制已生成的变量容器.变量容器在”refcount”变成0时就被销毁.当任何关联到某个变量容易的变量离开它的作用域(比如:函数执行结束),或者对变量调用了unset()函数,”refcount”就会减1,下面例子就能说明:

<?php    $a = "new string";    $b = $c = $a;    xdebug_debug_zval('a');    unset($b, $c);    xdebug_debug_zval('a');    ?>
以上代码会输出:

a: (refcount=3, is_ref=0)='new string' a: (refcount=1, is_ref=0)='new string'
如果我们现在执行unset($a),$包含的类型和值的这个容器就会从内存删除

### 复合类型

当考虑像array和object这样的复合类型时,事情会稍微有些复杂.与标量(scalar)类型的值不同,array和object类型的变量把它们的成员或属性存在自己的符号表中.这意味着下面的例子将生成三个zval变量容器

<?php        $a = array('meaning' => 'life', 'number' => 42);        xdebug_debug_zval('a');    ?>
以上代码输出:

a: (refcount=1, is_ref=0)=array ('meaning' => (refcount=1, is_ref=0)='life', 'number' => (refcount=1, is_ref=0)=42)
这三个zval变量容器是:a,meaning,number.增加和减少refcount的规则和上面提到的一样特例,添加数组本身作为数组元素时:

<?php    $a = array('one');     $a[] = &$a;     xdebug_debug_zval('a');    ?>
以上代码输出的结果:

a: (refcount=2, is_ref=1)=array (0 => (refcount=1, is_ref=0)='one', 1 => (refcount=2, is_ref=1)=...)
可以看到数组a和数组本身元素a[1]指向的变量容器refcount为2

当对数组$a调用unset函数时,$a的refcount变为1,发生了内存泄漏
清理变量容器的问题。

尽管不再有某个作用域中的任何符号指向这个结构(就是变量容器),由于数组元素”1&Prime;仍然指向数组本身,所以这个容器不能被消除.因为没有另外的符号指向它,用户没有办法清除这个结构,结果就会导致内存泄漏.庆幸的是,php将在请求结束时清除这个数据结构,但是php清除前,将耗费不少内存空间。

### 回收周期

5.3.0PHP使用了新的同步周期回收算法,来处理上面所说的内存泄漏问题

首先,我们先要建立一些基本规则:

如果一个引用计数增加,它将继续被使用,当然就不再垃圾中.如果引用技术减少到零,所在的变量容器将被清除(free).就是说,仅仅在引用计数减少到非零值时,才会产生垃圾周期(grabage  cycle).其次,在一个垃圾周期中,通过检查引用计数是否减1,并且检查哪些变量容器的引用次数是零,来发现哪部分是垃圾。

![Image] (../images/441292.png)

PHP的垃圾回收机制的原理是什么

为避免不得不检查所有引用计数可能减少的垃圾周期,这个算法把所有可能根(possible roots  都是zval变量容器),放在根缓冲区(root buffer)中(用紫色标记),这样可以同时确保每个可能的垃圾根(possible  garbage root)在缓冲区只出现一次.仅仅在根缓冲区满了时,才对缓冲区内部所有不同的变量容器执行垃圾回收操作。
