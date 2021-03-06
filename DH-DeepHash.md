#  (DH) Deep Hash
 
Provides Access to nested structures using dot notation<br>
`Dot.Notation` getters and setters for Nested Arrays, Objects and \Closures

Provides set of static methods and dynamic class.

```

 NO MORE FLAGS !!!!


    TODO:
        TRAIT

        iDeepHash


    TODO:
     rekey("*.from.*.key:*.to.*.new_name ...")
     getW("*.from.*.key:*.to.*.new_name ...")
     get(["from" => "aaa.bbb"]) : ["aaa.bbb" => $val]
     getW("from.key:to.name") : ["to" => ["name" => $val]]
     getW("**") - resolve all return result

     doc trait\DH

     doc iDeepHash

    -
     no errors when asking for partially resolved method/closure : return partially resolved closure
       dh[arity3closure.a.b] : arity1closure

    -
     couple paragraphs what does this class do DH <=> dot-notation, modification, extraction, merging, renaming

    -
     iterators
       leaf-first
       stucture-only
       leaf-only
       structure (how to descend)

     flatten (dh => dot-notation, resolve=true)

     dh - array|object - no support for \Closure as a root DH object

    - idea for iterator
        public function toArray() {
          return get_object_vars($this);
        }

    - array methods:     last, first, MD5

 ```

## Basic Static Methods
    DH::get($dh, "a.b.c")               ~=   $dh['a']['b']['c'] + Exceptions
    DH::get($dh, "a.b.c", $default)     ~=   $dh['a']['b']['c'] ?? $default
    DH::get($dh, "?a.b.c")              ~=   DH::get($dh, "a.b.c", null)
    DH::set($dh, "a.b.c", $value)       ~=   $dh['a']['b']['c'] = $value
    DH::getW($dh, "*.names.*.(age|sex)")  ~= extract DH subset
    DH::getQ($dh, "*.name.first")       ~=   extract data from DH. ["*" => value]
    DH::any($dh, "a.b.c b.c.d")         ~=   first non-empty value
    DH::remove($dh, "a.b.c")            ~=   unset($dh['a']['b']['c'])

<details>
<summary>:large_blue_circle: Objects and Closures Traversal Details ...</summary>

### Closures Traversal
    closure.xxx            -- if closure have 0-arity, resolve apply "xxx" to result
    closure.xxx            -- if closure have 1-arity, return closure(xxx)
    closure.arg1.arg2...   -- if closure have N-arity, return closure(...$n_args)
						      apply remaining path to result
    variadicClosure.x.y... -- return variadicClosure(...$remaining_path)

### Object Traversal
    object.path.a.b.c...
        if object support \hb\deephash\GetInterface  - GET CONTEXT
          - return object->_q($remaining_path)
        if object support \hb\deephash\SetInterface  - SET CONTEXT
          - return object->_set($remaining_path, $value)
		check for special object-only path syntax
        if object is \ArrayAccess
          - array access ONLY  - see exceptions below
          - STOP all other checks
        if object is NOT \ArrayAccess
          - method  (same logic as for closure traversal)
          - property
          - __invoke()
          - __toArray()
        NOT-FOUND-EXCEPTION (when strict and no-default value)
</details>

<details>
<summary>:large_blue_circle: Path syntax extensions for objects ...</summary>

    *Special "object-only" cases*
    `object.@property`    	-- enforce property
    `object.&method`       		-- enforce method call (only usable for \ArrayAccess case)
    `object.:property`     	-- static property || CONSTANT (all upper case)
</details>

## iDeepHash class
 ```
$dh = DH::i();                       iDeepHash([])
$dh = DH::i(['a' => 1, ...]);        iDeepHash(iterable)
$dh = DH::i([]);                     iDeepHash([])
$dh = DH::ref(&$existing_array);     iDeepHash by reference
$dh = DH::create(['a.b.c' => 1,..]); $dh=DH::i(); $dh->set(...)
$dh()                                @return &internal_data
echo $dh["dot.path"]		     read access deep element
echo $dh["?dot.path"]		     non-strict access
echo $dh[["a", "b", "c"]]	     array path
$dh["dot.path"] = "value"	     write access deep element
$dh->{$method} 			     all methods from DH class
```

## Methods
all methods receive array|\hb\DH|object as first argument

### DH::get($dh, string|array $path, $default) : mixed | Exception
    DH::get($dh, "path", $default) : value
    DH::get($dh, ["path", ...], $default) : [$value, ...]
    DH::get($dh, ["key" => "path", ...], $default) : [key => $value, ...]

    Examples:
      $value = DH::get($dh, "a.b.c");
      [$v1, $v2] = DH::get($dh, ["path.1", "path.2"]);
      $nameFL = DH::get($dh, ["f" => "name.first", "l" => "name.last"]);


### DH::first($dh, string|array $pathlist) : ?value
    get first EXISTING value OR NULL

    DH::first($dh, "path1 path2 ...") : value

### DH::any($dh, string|array $pathlist) : ?value
    get first NON-EMPTY value OR NULL

    DH::any($dh, "path1 path2 ...") : value


### DH::\&getRef($dh, string|array $path, $autocreate = true) => \&$value | Exception
    get element's reference
<details>
<summary>Examples:</summary>

    $valueRef = &DH::getRef($dh, "a.b.c");
    $valueRef = (int) $valueRef * 2 -1;
    $valueRef = &DH::getRef($dh, ["a.b", "cc", "dd"]);
</details>

### DH::set($dh, string|array $path, value)
same as getRef($path, autocreate) + assign

    DH::set("path", value)
    DH::set("path", null)  // delete key
    DH::set(["path" => value, ...])

### DH::remove($dh, "a.b.c")            ~=   unset($dh['a']['b']['c'])
    same as DH::set($dh, "a.b.c", null)

---
#### :cherries:	Wildcard Path (wpath)
used by `getW`, `setW`, `getP`, `setP` and some other commands
Set of wildcard paths delimited by space or "\n" or "\t"

	"aa.*.bb" 	 - all elements in $dh["aa"] that have subkey "bb"
				   "aa.xx.bb" - included, "aa.xx.yy.bb" - not 
	"aa.**.bb" 	 - all elements and subelements in $dh["aa"] that have subkey "bb"
				   "aa.xx.bb" and "aa.xx.yy.bb" - included
	"aa bb" 	 - $dh["aa"] and $dh["cc"]
	"aa.(bb|cc)"     - $dh["aa"]["bb"] && $dh["aa"]["cc"] (if present)
	"aa.* -aa.bb"    - all subelements from "aa" with an exception of "aa.bb"

### DH::getW($dh, wpath) : $dh_subset
wpath - Wildcard Path - see below

    DH::getW(wildcard) - extract SUBSET from $dh, @return new DH
    DH::getW(wpath) : array [path => .. => value]        // DH => DH
    DH::getW($dh, "path path.* path.**.name path.(k1|k2) -path") : array [path => .. => value]

<details>
<summary>Examples:</summary>

    $dh = [
	1 => ["name" => ["first" => "Joe", "last" => "Black"], "age" => 6500], 
	2 => ["name" => ["first" => "Silent", "last" => "Bob"], "age" => 28]]
    ];
    DH::getW($dh, "*.name.first");
        [ 1 => ["name" => ["first" => "Joe"]], [2 => ["name" => ["first" => "Silent"]] ]
    DH::getW($dh, "2.name.(first|last)");
	    [1 => ["name" => ["first" => "Joe", "last" => "Bob"]
    DH::getW($dh, "1.name.first 2.* -2.name");
	    [1 => ["name" => ["first" => "Joe"]], 2 => ["age" => 28]]
	    
</details>

### DH::update($dh, $dh2) 
update $dh from $dh2 - @see `array_replace_recursive`<br>can use to store back values extracted by getW method

### DH::setW($dh, wpath, $value)
update / remove many items

    `DH::setW("*.data.ssn", "hidden")`
    `DH::setW("*.data.ssn", null)`         // delete items

###  DH::getP($dh, wpath) : ["dot.path" => $value]
      DH::getP - "path" => value  ("path" => $value presentation of deep structure)
      DH::getP(wpath)
      DH::set($getP)  // save data back
<details>
<summary>Examples:</summary>

    $dh = [
	1 => ["name" => ["first" => "Joe", "last" => "Black"], "age" => 6500], 
	2 => ["name" => ["first" => "Silent", "last" => "Bob"], "age" => 28]]
    ];
    DH::getP($dh, "*.name.first");
        [ "1.name.first" => "Joe", ["2.name.first" => "Silent"]
    DH::getW($dh, "1.name.first 2.* -2.name");
	    ["1.name.first" => "Joe"], "2.age" => 28]
	    
</details>

#### :cherries:	Q-Path (qpath)
used by `getQ`, `setQ` and some other commands<br>
syntax similar to wildcard path, but `"?"` used instead of `"*"` and only this forms supported:

    "aa.?.bb"
    "aa.?.bb.?"
    "aa.(xx|yy)"


### DH::getQ($dh, qpath) : [? => value]
    DH::getQ("path.?.name") : array [? => value, ...]
    DH::getQ("path.?.path2.?.name") : array [? => ? => value]
    DH::getQ("?.(name|age)") : array [? => (name|age) => value, ...]
 <details>
 <summary>Examples:</summary>

    $dh = [
	1 => ["name" => ["first" => "Joe", "last" => "Black"], "age" => 6500], 
	2 => ["name" => ["first" => "Silent", "last" => "Bob"], "age" => 28]]
    ];
    DH::getQ($dh, "?.name.first");
        [ 1 => "Joe", 2 => "Silent"]
    DH::getQ($dh, "?.name.?");
        [ 1 => ["first" => "Joe", "last" => "Black"], 2 => ["first" => "Silent", "last" => "Bob"] ]
    DH::getW($dh, "1.name.(first|last)");
	    ["first" => "Joe", "last" => "Black"]
    DH::setQ($dh, "?.name.first", [1 => 'Ann', 2 => 'Jill']);

</details>

### DH::setQ($dh, qpath, array $getQ)  - opposite of getQ

    DH::setQ($dh, "path.?.name", [? => value, ...])
    Ex: DH::setQ($dh, "?.name.first", [1 => "Jim", 2 => "Loud"])

### DH::getV($dh, "view 2.0 syntax path")
	TODO
 
### DH::setCB($dh, wpath, $callback(array $path, $current) : $new_value)
update DH via callback

	Ex: `DH::setCB($dh, "*.name.(first|last)", fn($path, $v) => ucfirst($v))`
 
### DH::setQCB($dh, qpath, $callback(array $qpath, $current) : $new_value)
update DH via callback

### DH::**getArrayRef(\$dh, \$path)** : &$array | Exception
{ getRef; if not array - initialize as array, return reference }

###  DH::getCallback : \Closure|null|Exception
{ getParent(); if instance return $instance->method(...); if \Closure - return it}
	  
## Array methods
similar to {DH::getArrayRef; do array_XXX on reference}
* DH::shift
* DH::pop
* DH::unshift
* DH::push
 
## Merging datasets
* `DH::merge($dh, $dh2, callback($path, $current_value=null, $new_value=null) : ?result` <br>universal merge method, developers can implement any logic there<br>null result considered as remove item
*  `DH::update($dh, $dh2)`            -- override ALL nodes (existing and new) - array_recursive_replace
*  `DH::updateExisting($dh, $dh2)`    -- override existing nodes ONLY
*  `DH::mergeNew(dh, $dh2)`         -- import new Nodes Only

# Data Caching
caching layer around your closure/instance_methods<br>
`DH::cacher(\Closure(array $path)|instance, $cacheAdapter=null, $cacheAdapterArgs = []) : \Closure`<br>
Default behaviour: cache in php memory, available cache adapters: apc, memcached, redis, mysql, json-file

Usage: `$dh["path"] = DH::cacher( $my_closure );`

# Core DH0 methods
```
  DH0::q(dh, array $path, $default) => $value | RuntimeException | InvalidArgumentException
  DH0::_q(array $path) => [], [value], null     // MOST CORE METHOD
  DH0::_getRef(array $path)  => &$value | Exception
  DH0::_remove(array $path)
  DH0::wildcard($wpath) - \Generator  - $path => $value
  DH0::wildcardRef($wpath, $autocreate=1) - \Generator - iterator path => &$value
  DH0::_set(array $path, $value)
```


