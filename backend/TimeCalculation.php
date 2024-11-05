<?php
class TimeCalculation
{
    private $startTime;
    private $endTime;
    private $timeArrayInMinutes;
    private $timeArrayInHoursAndMinutes;

    public function __construct($startTime, $endTime, $timeArrayInMinutes)
    {
        $this->startTime = new DateTime($startTime);
        $this->endTime = new DateTime($endTime);
        $this->timeArrayInMinutes = $timeArrayInMinutes;
        $this->timeArrayInHoursAndMinutes = [];
    }

    public function calculateScheduledTime()
    {
        $this->timeArrayInHoursAndMinutes[] = $this->startTime->format('H:i');
        $currentTime = clone $this->startTime;

        foreach ($this->timeArrayInMinutes as $time) {
            $currentTime->add(new DateInterval("PT{$time}M"));
            $this->timeArrayInHoursAndMinutes[] = $currentTime->format('H:i');
        }

        // Check if the calculated end time matches the provided end time
        $calculatedEndTime = end($this->timeArrayInHoursAndMinutes);
        $providedEndTime = $this->endTime->format('H:i');

        if ($calculatedEndTime !== $providedEndTime) {
            throw new Exception("Calculated end time ($calculatedEndTime) does not match the provided end time ($providedEndTime)");
        }
    }

    public function printTime()
    {
        return json_encode($this->timeArrayInHoursAndMinutes);
    }

    public function __toString()
    {
        return $this->printTime();
    }
}

// Example usage
// $arrayInMinutes = [180, 120, 120, 120, 120];
// $input = new TimeCalculation('05:00', '16:00', $arrayInMinutes);

// try {
//     $input->calculateScheduledTime();
//     echo $input; // This will call __toString() method
// } catch (Exception $e) {
//     echo "Error: " . $e->getMessage();
// }
