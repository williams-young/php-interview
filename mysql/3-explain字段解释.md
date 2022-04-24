EXPLAIN列的解释：

table：显示这一行的数据是关于哪张表的

type：这是重要的列，显示连接使用了何种类型。从最好到最差的连接类型为const、eq_reg、ref、range、indexhe和ALL

possible_keys：显示可能应用在这张表中的索引。如果为空，没有可能的索引。可以为相关的域从WHERE语句中选择一个合适的语句。这个列表是在优化过程的早期创建的，因此有些列出来的索引可能对于后续优化过程是没有用的。

key： 实际使用的索引。如果为NULL，则没有使用索引。很少的情况下，MYSQL会选择优化不足的索引。这种情况下，可以在SELECT语句中使用USE INDEX（indexname）来强制使用一个索引或者用IGNORE INDEX（indexname）来强制MYSQL忽略索引

如果该索引没有出现在possible_keys中，那么mysql选用的是出于另外的原因——例如，他可能选择了一个覆盖索引，哪怕没有where字句，换句话说，possible_keys揭示了哪一个索引能有助于提高查询效率，而key显示的是优化采用哪一个索引可以最小化查询成本。
key_len：使用的索引的长度。在不损失精确性的情况下，长度越短越好

ref：显示索引的哪一列被使用了，如果可能的话，是一个常数

rows：MYSQL认为必须检查的用来返回请求数据的行数

Extra：关于MYSQL如何解析查询的额外信息。将在表4.3中讨论，但这里可以看到的坏的例子是Using temporary和Using filesort，意思MYSQL根本不能使用索引，结果是检索会很慢

Type列的解释

Type：告诉我们对表使用的访问方式，主要包含如下集中类型。

all：全表扫描。

const：读常量，最多只会有一条记录匹配，由于是常量，实际上只须要读一次。

eq_ref：最多只会有一条匹配结果，一般是通过主键或唯一键索引来访问。

fulltext：进行全文索引检索。

index：全索引扫描。

index_merge：查询中同时使用两个（或更多）索引，然后对索引结果进行合并（merge），再读取表数据。

index_subquery：子查询中的返回结果字段组合是一个索引（或索引组合），但不是一个主键或唯一索引。

rang：索引范围扫描。

ref：Join语句中被驱动表索引引用的查询。

ref_or_null：与ref的唯一区别就是在使用索引引用的查询之外再增加一个空值的查询。

system：系统表，表中只有一行数据；

unique_subquery：子查询中的返回结果字段组合是主键或唯一约束。

Extra字段解释

Extra：查询中每一步实现的额外细节信息，主要会是以下内容。

Distinct：查找distinct 值，当mysql找到了第一条匹配的结果时，将停止该值的查询，转为后面其他值查询。

Full scan on NULL key：子查询中的一种优化方式，主要在遇到无法通过索引访问null值的使用。

Range checked for each record (index map: N)：通过 MySQL 官方手册的描述，当 MySQL Query Optimizer 没有发现好的可以使用的索引时，如果发现前面表的列值已知，部分索引可以使用。对前面表的每个行组合，MySQL检查是否可以使用range或 index_merge访问方法来索取行。

SELECT tables optimized away：当我们使用某些聚合函数来访问存在索引的某个字段时，MySQL Query Optimizer 会通过索引直接一次定位到所需的数据行完成整个查询。当然，前提是在 Query 中不能有 GROUP BY 操作。如使用MIN()或MAX()的时候。

Using filesort：当Query 中包含 ORDER BY 操作，而且无法利用索引完成排序操作的时候，MySQL Query Optimizer 不得不选择相应的排序算法来实现。

Using index：所需数据只需在 Index 即可全部获得，不须要再到表中取数据。

Using index for group-by：数据访问和 Using index 一样，所需数据只须要读取索引，当Query 中使用GROUP BY或DISTINCT 子句时，如果分组字段也在索引中，Extra中的信息就会是 Using index for group-by。

Using temporary：当 MySQL 在某些操作中必须使用临时表时，在 Extra 信息中就会出现Using temporary 。主要常见于 GROUP BY 和 ORDER BY 等操作中。

Using where：如果不读取表的所有数据，或不是仅仅通过索引就可以获取所有需要的数据，则会出现 Using where 信息。

Using where with pushed condition：这是一个仅仅在 NDBCluster存储引擎中才会出现的信息，而且还须要通过打开 Condition Pushdown 优化功能才可能被使用。控制参数为 engine_condition_pushdown 。

Impossible WHERE noticed after reading const tables：MySQL Query Optimizer 通过收集到的统计信息判断出不可能存在结果。

No tables：Query 语句中使用 FROM DUAL或不包含任何 FROM子句。

Not exists：在某些左连接中，MySQL Query Optimizer通过改变原有 Query 的组成而使用的优化方法，可以部分减少数据访问次数。