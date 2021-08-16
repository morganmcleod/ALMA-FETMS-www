<html>

<head>
    <title>Look Out World</title>
</head>

<body>

    <?php
    class B {
        var $name = "base";

        public function __construct() {
        }

        public function printName() {
            echo "My name is ", $this->name, ".<p>";
        }
    }

    class D extends B {
        var $name = "derivedInit";

        public function __construct() {
            parent::__construct();
            $this->name = "derivedConst";
        }
    }

    echo "does this work at all?<p>";

    $b = new B();
    $b->printName();

    $d = new D();
    $d->printName();

    ?>

</body>

</html>
