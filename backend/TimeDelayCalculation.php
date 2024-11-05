<?php
class Delay
{
    private $delay;
    private $position;
    private $scheduled_times;

    public function __construct($delay, $position, $scheduled_times)
    {
        $this->delay = $delay;
        $this->position = $position;
        $this->scheduled_times = $scheduled_times;
    }

    public function delayCalculation()
    {
        if (!is_array($this->scheduled_times)) {
            return json_encode([]);  // Return empty array if no scheduled times
        }

        $delayed_times = [];
        foreach ($this->scheduled_times as $index => $time) {
            if ($index >= $this->position) {
                // Add delay only to stations after and including the delay position
                $datetime = new DateTime($time);
                $datetime->add(new DateInterval('PT' . $this->delay . 'M'));
                $delayed_times[] = $datetime->format('Y-m-d H:i:s');
            } else {
                // Keep original time for stations before delay position
                $delayed_times[] = $time;
            }
        }

        return json_encode($delayed_times);
    }
}
