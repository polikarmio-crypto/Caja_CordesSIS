<?php
require_once __DIR__ . '/CustomNode.php';

/**
 * Clase CustomQueue
 * Estructura de datos tipo Cola (FIFO - First In, First Out)
 */
class CustomQueue {
    private $front;
    private $rear;
    private $size;

    public function __construct() {
        $this->front = null;
        $this->rear = null;
        $this->size = 0;
    }

    /**
     * Encolar: Inserta un elemento al final de la cola.
     */
    public function enqueue($item) {
        $newNode = new CustomNode($item);
        if ($this->isEmpty()) {
            $this->front = $newNode;
            $this->rear = $newNode;
        } else {
            $this->rear->next = $newNode;
            $newNode->prev = $this->rear;
            $this->rear = $newNode;
        }
        $this->size++;
    }

    /**
     * Desencolar: Remueve y retorna el elemento al frente de la cola.
     */
    public function dequeue() {
        if ($this->isEmpty()) {
            return null;
        }
        $removedNode = $this->front;
        $this->front = $this->front->next;
        if ($this->front !== null) {
            $this->front->prev = null;
        } else {
            $this->rear = null;
        }
        $this->size--;
        return $removedNode->data;
    }

    /**
     * Obtener el elemento del frente de la cola sin removerlo.
     */
    public function peek() {
        if ($this->isEmpty()) {
            return null;
        }
        return $this->front->data;
    }

    /**
     * Verifica si la cola está vacía.
     */
    public function isEmpty() {
        return $this->front === null;
    }

    /**
     * Retorna la cantidad de elementos en la cola.
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * Convierte la cola a un array asociativo plano para facilitar su recorrido en la vista.
     */
    public function toArray() {
        $arr = [];
        $current = $this->front;
        while ($current !== null) {
            $arr[] = $current->data;
            $current = $current->next;
        }
        return $arr;
    }
}
?>
