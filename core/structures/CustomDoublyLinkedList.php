<?php
require_once __DIR__ . '/CustomNode.php';

/**
 * Clase CustomDoublyLinkedList
 * Estructura de datos tipo Lista Doblemente Enlazada.
 */
class CustomDoublyLinkedList {
    private $head;
    private $tail;
    private $size;

    public function __construct() {
        $this->head = null;
        $this->tail = null;
        $this->size = 0;
    }

    /**
     * Inserta un elemento al final de la lista (orden cronológico ascendente).
     */
    public function insertAtEnd($item) {
        $newNode = new CustomNode($item);
        if ($this->isEmpty()) {
            $this->head = $newNode;
            $this->tail = $newNode;
        } else {
            $this->tail->next = $newNode;
            $newNode->prev = $this->tail;
            $this->tail = $newNode;
        }
        $this->size++;
    }

    /**
     * Inserta un elemento al principio de la lista.
     */
    public function insertAtFront($item) {
        $newNode = new CustomNode($item);
        if ($this->isEmpty()) {
            $this->head = $newNode;
            $this->tail = $newNode;
        } else {
            $newNode->next = $this->head;
            $this->head->prev = $newNode;
            $this->head = $newNode;
        }
        $this->size++;
    }

    /**
     * Retorna el primer nodo (cabeza) de la lista.
     */
    public function getHead() {
        return $this->head;
    }

    /**
     * Retorna el último nodo (cola) de la lista.
     */
    public function getTail() {
        return $this->tail;
    }

    /**
     * Retorna el tamaño de la lista.
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * Verifica si la lista está vacía.
     */
    public function isEmpty() {
        return $this->head === null;
    }

    /**
     * Convierte la lista enlazada a un array estándar de PHP.
     */
    public function toArray() {
        $arr = [];
        $current = $this->head;
        while ($current !== null) {
            $arr[] = $current->data;
            $current = $current->next;
        }
        return $arr;
    }
}
?>
