##1 写出 php 的 public、protected、private 三种访问控制模式的区别
public：公有，任何地方都可以访问
protected：继承，只能在本类或子类中访问，在其它地方不允许访问
private：私有，只能在本类中访问，在其他地方不允许访问

##2 关于trait

从基类继承的成员会被 trait 插入的成员所覆盖。优先顺序是来自当前类的成员覆盖了 trait 的方法，而 trait 则覆盖了被继承的方法。


