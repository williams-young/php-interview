PHP数组是一个神奇而强大的数据结构，数组既可以是连续的数组，也可以是存储
K-V映射的map。而在PHP7中，相比于PHP5，对数组进行了很大的修改。
### 一、数组的语义

本质上，PHP数组是一个有序的字典，它需要同时满足一下两个语义。

语义一：PHP数组是一个字典，存储着键—值（key—value）对。通过键可以快速地找到对应的值，键可以是整形，也可以是字符串。

语义二：PHP数组是有序的。这个有序是指插入顺序，遍历数组的时候，遍历元素的顺序应该和插入顺序一致，而不像普通字典一样是随机的。
为了实现语义一，PHP用HashTable来存储键—值对，但是HashTable本身并不能保证语义二，PHP不同版本都对HashTable进行了额外的设计来保证有序，下面会进行介绍。

### 二、数组的概念

key: 键，通过它可以快速检索到对应的value。一般为数字或字符串。
value : 值，目标数据。可以是复杂的数据结构。
bucket： 桶，HashTable中存储数据的单元。用来存储key和value以及辅助信息的容器。
slot: 槽，HashTable有多个槽，一个bucket必须从属于具体的某一个slot，一个slot下可以有多个bucket。
哈希函数： 需要自己实现，在存储的时候，会对key应用哈希函数确定所在slot。
哈希冲突：当多个key经过哈希计算后，得出的slot的位置是同一个，那么就叫作哈希冲突。一般解决冲突的方法是链地址法和开放地址法。PHP采用链地址法，将同一个slot中的bucket通过链表链接起来。
在具体实现中，PHP基于上述基本概念对bucket以及哈希函数进行了一些补充，增加了hash1函数以生成h值，然后通过hash2函数散列到不同的slot。

### 三、php5数组实现
```
typedef struct bucket {  
    ulong h;                   /* 4字节 对char *key进行hash后的值，或者是用户指定的数字索引值/* Used for numeric indexing */
    uint nKeyLength;           /* 4字节 字符串索引长度，如果是数字索引，则值为0 */  
    void *pData;               /* 4字节 实际数据的存储地址，指向value，一般是用户数据的副本，如果是指针数据，则指向pDataPtr,这里又是个指针，zval存放在别的地方*/
    void *pDataPtr;            /* 4字节 引用数据的存储地址，如果是指针数据，此值会指向真正的value，同时上面pData会指向此值 */  
    struct bucket *pListNext;  /* 4字节 整个哈希表的该元素的下一个元素*/  
    struct bucket *pListLast;  /* 4字节 整个哈希表的该元素的上一个元素*/  
    struct bucket *pNext;      /* 4字节 同一个槽，双向链表的下一个元素的地址 */  
    struct bucket *pLast;      /* 4字节 同一个槽，双向链表的上一个元素的地址*/  
    char arKey[1];             /* 1字节 保存当前值所对于的key字符串，这个字段只能定义在最后，实现变长结构体*/  
} Bucket;
```
（1）这里bucket新增三个元素:
arkey： 对应HashTable设计中的key,表示字符串key。
h: 对应HashTable设计中的h,表示数字key或者字符串key的h值。
pData和pDataPtr: 对应HashTable设计中的value。
一般value存储在pData所指向的内存，pDataPtr是NULL，但如果value的大小等于一个指针的大小，那么不会再额外申请内存存储，而是直接存储在pDataPtr上，再让pData指向pDataPtr，可以减少内存碎片。

（2）为了实现数组的两个语义，bucket里面有pListLast、pListNext、pLast、pNext这4个指针，维护两种双向链表。一种是全局链表，按插入顺序将所有bucket全部串联起来，整个HashTable只有一个全局链表。另一个是局部链表，为了解决哈希冲突，每个slot维护着一个链表，将所有哈希冲突的bucket串联起来。也就是，每一个bucket都处在一个双向链表上。pLast和pNext分别指向局部链表的前一个和后一个bucket，pListLast和pListTNext则指向全部链表的前一个和后一个。

### 四、到这里分析一下，PHP7为什么要重写数组实现。

每一个bucket都需要一次内存分配。
key—value中的value都是zval。这种情况下，每个bucket需要维护指向zval的指针pDataPtr以及指向pDataPtr的指针pData。
为了保证数组的两个语义，每一个bucket需要维护4个指向bucket的指针。
以上原因，导致性能不好。

### 五、PHP7数组实现

既然都用HashTable，如果通过链地址法解决哈希冲突，那么链表是必然需要的，为了保证有序性，的确需要再维护一个全局链表，看起来PHP5已经是无懈可击了。
实际上，PHP7也是通过链地址法，但是此 “链” 非彼 “链”。PHP5的链表是在物理上得到链表，链表中bucket之间的上下游关系通过真实存在的指针来维护。而PHP7的链表是一种逻辑上的链表，所有bucket都分配在连续的数组内存中，不再通过指针来维护上下游关系，每一个bucket只维护下一个bucket在数组中的索引（因为是连续内存，通过索引可以快速定位到bucket），即可完成链表上的bucket的遍历。
好的，揭开PHP7数组底层结构的庐山真面目：

```
typedef struct _Bucket {
    zval              val;      /* 对应HashTable设计中的value */ 
    zend_ulong        h;        /* 对应HashTable设计中的h，表示数字key或者字符串key的h值。*/        
    zend_string      *key;      /* 对应HashTable设计中的key */          
} Bucket;
```

bucket可以分为3种：未使用、有效、无效。
未使用：最初所有bucket都是未使用状态。
有效：存储着有效的数据。
无效：当bucket上的数据被删除时，有效bucket就会变为无效bucket。
在内存分布上，有效和无效bucket会交替分布。但都在未使用bucket的前面。插入的时候永远在未使用bucket上进行，当无效bucket过多，而有效bucekt很少时，对整个bucket数组进行rehash操作这样稀疏的有效bucket就变得连续而紧密，部分无效bucket会被重新利用而变得有效，还有一部分有效bucket和无效bucket会被释放出来，重新变为未使用bucket。