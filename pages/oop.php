<?php
class TimingBetweenStops
{
    private $start;
    private $end;
    private $timeInMinutes;

    public function __construct($start, $end, $timeInMinutes)
    {
        $this->start = $start;
        $this->end = $end;
        $this->timeInMinutes = $timeInMinutes;
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getEnd()
    {
        return $this->end;
    }
    public function getTimeInminutes()
    {
        return $this->timeInMinutes;
    }

    public static function matchingPairs($start, $destination, $routes)
    {
        foreach ($routes as $route) {
            if (($route->getStart() == $start && $route->getEnd() == $destination) ||
                ($route->getStart() == $destination && $route->getEnd() == $start)
            ) {
                return $route->getTimeInminutes();
            }
        }
        return null;
    }
}

class InputFromDetails
{
    private $arrayOfStops;
    private $arrayOfTime;

    public function __construct($arrayOfStops)
    {
        $this->arrayOfStops = $arrayOfStops;
        $this->arrayOfTime = [];
    }
    public function calculateTime($routes)
    {
        for ($i = 0; $i < count($this->arrayOfStops) - 1; $i++) {
            $returnResult = TimingBetweenStops::matchingPairs($this->arrayOfStops[$i], $this->arrayOfStops[$i + 1], $routes);
            if ($returnResult === null) {
                throw new Exception("No route found between {$this->arrayOfStops[$i]} and {$this->arrayOfStops[$i + 1]}");
            }
            $this->arrayOfTime[] = $returnResult;
        }
        return $this->returnDetails();
    }
    public function returnDetails()
    {
        return json_encode($this->arrayOfTime);
    }
}


$routes = [
    new TimingBetweenStops("Thiruvananthapuram", "Kollam", 120),
    new TimingBetweenStops("Kollam", "Pathanamthitta", 90),
    new TimingBetweenStops("Kollam", "Alappuzha", 120),
    new TimingBetweenStops("Pathanamthitta", "Alappuzha", 90),
    new TimingBetweenStops("Pathanamthitta", "Kottayam", 90),
    new TimingBetweenStops("Alappuzha", "Kottayam", 60),
    new TimingBetweenStops("Alappuzha", "Ernakulam", 120),
    new TimingBetweenStops("Kottayam", "Idukki", 150),
    new TimingBetweenStops("Kottayam", "Ernakulam", 90),
    new TimingBetweenStops("Idukki", "Ernakulam", 180),
    new TimingBetweenStops("Ernakulam", "Thrissur", 120),
    new TimingBetweenStops("Thrissur", "Kozhikode", 180),
    new TimingBetweenStops("Thrissur", "Palakkad", 120),
    new TimingBetweenStops("Thrissur", "Malappuram", 150),
    new TimingBetweenStops("Palakkad", "Malappuram", 90),
    new TimingBetweenStops("Malappuram", "Kozhikode", 120),
    new TimingBetweenStops("Malappuram", "Wayanad", 180),
    new TimingBetweenStops("Kozhikode", "Wayanad", 120),
    new TimingBetweenStops("Kozhikode", "Kannur", 120),
    new TimingBetweenStops("Wayanad", "Kannur", 150),
    new TimingBetweenStops("Kannur", "Kasaragod", 120),
    new TimingBetweenStops("Ernakulam", "Kasaragod", 480),
    new TimingBetweenStops("Thiruvananthapuram", "Alappuzha", 180),
    new TimingBetweenStops("Thiruvananthapuram", "Pathanamthitta", 210),
];
