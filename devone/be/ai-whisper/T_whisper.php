<?php
class T_whisper extends MY_Controller
{
    private $base_audio = "/home/one/project/one/one-media/voice2text/";

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo "API Speech To Text With Whisper";
    }

    public function whisper_1()
    {
        $prm = $this->sys_input;
        $audio_file = $prm['audio_file'] ?? "";
        $persons = $prm['config']['speakerCounts'] ?? 2;
        $language = $prm['config']['language'] ?? "id";

        $audio_path = $this->base_audio . $audio_file;
        if (!file_exists($audio_path)) {
            $this->sys_error("Audio file not found");
        };

        $this->load->library('Ai_whisper');
        $this->ai_whisper->whisperLanguage = $language;
        $result = $this->ai_whisper->with_diarization($audio_path, $persons);
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
}
