<?php

require_once __DIR__ . '/../includes/config.php';

class FeedbackManager {
    private $feedbackFile;

    public function __construct($baseDir = '.') {
        $explorerDir = $baseDir . DIRECTORY_SEPARATOR . '.explorer';
        $this->feedbackFile = $explorerDir . DIRECTORY_SEPARATOR . 'feedbacks.json';
        $this->ensureFeedbackFile();
    }

    private function ensureFeedbackFile() {
        if (!file_exists($this->feedbackFile)) {
            $initialData = [];
            file_put_contents($this->feedbackFile, json_encode($initialData, JSON_PRETTY_PRINT));
        }
    }

    public function addFeedback($message) {
        if (empty($message)) {
            throw new Exception('Le message ne peut pas être vide');
        }

        $message = trim($message);

        if (strlen($message) > 500) {
            throw new Exception('Le message est trop long (maximum 500 caractères)');
        }

        if (strlen($message) < 1) {
            throw new Exception('Le message est trop court');
        }

        $feedbacks = $this->loadFeedbacks();

        $newFeedback = [
            'id' => $this->generateUniqueId(),
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'created_at' => date('Y-m-d H:i:s'),
            'timestamp' => time()
        ];

        array_unshift($feedbacks, $newFeedback);

        $this->saveFeedbacks($feedbacks);

        return $newFeedback;
    }

    public function getAllFeedbacks() {
        return $this->loadFeedbacks();
    }

    private function generateUniqueId() {
        return time() . '_' . bin2hex(random_bytes(8));
    }

    private function loadFeedbacks() {
        if (!file_exists($this->feedbackFile)) {
            return [];
        }

        $content = file_get_contents($this->feedbackFile);
        $feedbacks = json_decode($content, true);

        return $feedbacks ?: [];
    }

    private function saveFeedbacks($feedbacks) {
        $content = json_encode($feedbacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($this->feedbackFile, $content) === false) {
            throw new Exception('Impossible de sauvegarder les feedbacks');
        }
    }

    public function getFeedbackCount() {
        $feedbacks = $this->loadFeedbacks();
        return count($feedbacks);
    }

    public function deleteFeedback($feedbackId) {
        if (empty($feedbackId)) {
            throw new Exception('L\'ID du feedback est requis');
        }

        $feedbacks = $this->loadFeedbacks();
        $initialCount = count($feedbacks);

        // Filtrer pour exclure le feedback avec l'ID correspondant
        $feedbacks = array_values(array_filter($feedbacks, function($feedback) use ($feedbackId) {
            return $feedback['id'] !== $feedbackId;
        }));

        // Vérifier si un feedback a été supprimé
        if (count($feedbacks) === $initialCount) {
            throw new Exception('Feedback introuvable');
        }

        $this->saveFeedbacks($feedbacks);

        return true;
    }
}
