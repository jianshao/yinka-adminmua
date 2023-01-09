定制化开发请加qq 1424173603
### 支持的操作
- 异步/同步加载 => layui table的加载
- 节点单元格动态小图标工具编辑、新增、删除和排序操作 => 增删改查
- 排序 => 全表排序、指定节点/节点id 排序
- 分页
- 指定节点或节点id删除节点及其叶子节点
- 叶子节点选中、上级节点自动选中、反之、自动清除选中
- 节点折叠记忆
- 重载
- 局部刷新
- 关键字检索及检索前折叠状态记忆
- 重置搜索前折叠状态
- 传参新增树形叶子节点、数据为空时，为空白节点
- 传参新增最上级节点
- 基于table tool事件进行aop增强，实现树形表格编辑、修改及下拉框和时间选择框的整合
- 指定节点或者节点id展开/关闭节点及其叶子节点
- 全部展开/折叠
- layui table api方式的操作
- 配置简单
- 小图标可自定义
- 节点id去重功能
- 无需额外的css样式

### 组件api
|方法名           |    是否参数  |     功能           |    描    述                                    |
|   ----          |    ---      |    ----           |        ------                                  |
| on              |     有      | 方法注册           | event:事件名称 callback:回调方法                 |
| callbackFn      |     有      | 注册方法回调       | event:事件名称 params:回调参数                 |
| render          |     有      | 表格树实例化       | 在layui table render参数基础上加上 treeConfig属性 |

#### treeConfig

```
json
    treeConfig:{ //表格树配置
        showField:'name' //树形显示字段
        ,treeid:'id' //节点id
        ,treepid:'pid'//节点父级id
        ,iconClass:'layui-icon-layer' //小图标class样式
        ,showToolbar: true //展示工具栏 false不展示 true展示
   }

```

### 实例对象api
| 方法名          |   功能描述
|----             |  -----------                                                |
| reload          |  表格重载 参数详解layui table reload方法                      |
| refresh         |  表格局部刷新 data(数组)：节点数据 为空时以现有数据局部刷新             |
| sort            |  表格树节点逐级排序 field：排序字段 desc => true降序、false升序 |   
| sortByTreeNode  |  指定节点/节点id逐级排序 params:节点/节点id field：排序字段 isAsc:true升序 false降序 |
| addTopTreeNode  |  增加最上级节点  data(object) 增加单个节点                     |
| delTreeNode     |  指定节点/节点id删除节点及其叶子节点                            | 
| getCheckedTreeNodeData | 获取选中节点数据   |
| getTableTreeData | 获取整个表格树数据       | 
| closeTreeNode    | 指定节点/节点id折叠节点  |
| openTreeNode     | 指定节点/节点id展开节点  |
| closeAllTreeNodes| 折叠所有节点             |
| openAllTreeNodes | 展开所有节点             |
| getTreeOptions   | 获取表格树参数配置       |
| keywordSearch    | 关键词检索 关键词参数     |
| clearSearch      | 重置表格树到检索前折叠状态 |

### 监听表格树event tool事件

```
        tableTree.on('tool(tableEvent)',function (obj) {
            var field = obj.field; //单元格字段
            var value = obj.value; //修改后的值
            var data = obj.data; //当前行数据
            var event = obj.event; //当前单元格事件
            if(event === 'update'){
               obj.update(value); //数据更新
            }
            
            //event为del为删除 add则新增 async则异步请求数据
            if(event === 'del'){
               obj.del(); //删除节点及其子节点
            }
            if(event === 'add'){ //点击操作栏加号图标时触发
                //异步、同步都可以使用
                //obj.add(arr)生成表格树,arr参数为数组，数组中元素的treeid字段值重复则被过滤掉
                obj.add([]) //参数不传或为空数组时 => 新增空行
            }
            if(event === 'async'){ //点击方向箭头小图标时触发
                //可ajax异步请求后台数据,回调obj.async(arr)生成表格树,arr参数为数组
                //数组中元素的treeid字段值重复则被过滤掉
                obj.async([{"id":'abc',"treeName":'abc',"permissionId ":'abc',"sort":'3333',createDate:'2020-02-02',type:'1'}]);
            }
       });
```

### 监听树形表格复选框

```
        tableTree.on('checkbox(tableEvent)', function(obj){
            console.log(obj.checked); //当前是否选中状态
            console.log(obj.data); //选中行的相关数据
            console.log(obj.type); //如果触发的是全选，则为：all，如果触发的是单选，则为：one
        });
```

### 监听树形表格工具条

```
        table.on('toolbar(tableEvent)', function(obj){
            treeTable.addTopTreeNode();         //新增最上级节点
            treeTable.getCheckedTreeNodeData(); //获取选中行的树状数据
            treeTable.getTableTreeData();       //获取表格树所有数据
            treeTable.closeTreeNode('5');       //指定tr/节点id折叠节点
            treeTable.openTreeNode(5);          //指定tr/节点id展开相对应树节点
            treeTable.closeAllTreeNodes();      //折叠所有节点
            treeTable.openAllTreeNodes();       //展开所有节点
            treeTable.getTreeOptions();         //获取表格配置
            treeTable.reload();                 //表格树reload
            treeTable.delTreeNode('1');         //指定tr/节点id删除节点及相关叶子节点
            treeTable.clearSearch();            //重置搜索前表格树折叠状态
            treeTable.refresh(rs);              //传数据刷新、不传刷新当前表格树，但不重载。
            treeTable.sortByTreeNode(5,'sort',false);  //指定节点/节点id和字段对其叶子节点逐级排序
        });
```

### 监听树形表格排序

```
        table.on('sort(tableEvent)', function(obj){
            treeTable.sort({field:obj.field,desc:obj.type === 'desc'}) //整个表格树节点及其叶子逐级排序
        });
```

### 效果图
![a](https://images.gitee.com/uploads/images/2020/0520/181639_616384ec_1588195.png "1.png")

![b](https://images.gitee.com/uploads/images/2020/0520/184740_aebc70ff_1588195.gif "tableEdit.gif")