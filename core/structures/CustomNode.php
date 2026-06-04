<?php
/**
 * Clase CustomNode
 * Nodo base para estructuras de datos dinámicas (colas, listas doblemente enlazadas).
 */
class CustomNode {
    public $data;
    public $next;
    public $prev;

    public function __construct($data) {
        $this->data = $data;
        $this->next = null;
        $this->prev = null;
    }
}
?>
