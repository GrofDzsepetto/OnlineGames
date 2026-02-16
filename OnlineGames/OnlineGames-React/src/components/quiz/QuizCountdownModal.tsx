import { useEffect, useState } from "react";
import "../../styles/Quiz/components/QuizCountdownModal.css";

type QuizCountdownModalProps = {
  seconds?: number;
  onComplete?: () => void;
};

export default function QuizCountdownModal({
  seconds = 3,
  onComplete,
}: QuizCountdownModalProps) {
  const [count, setCount] = useState<number>(seconds);

  useEffect(() => {
    if (count <= 0) {
      const timeout = setTimeout(() => {
        onComplete?.();
      }, 500);
      return () => clearTimeout(timeout);
    }

    const timer = setTimeout(() => {
      setCount((prev) => prev - 1);
    }, 1000);

    return () => clearTimeout(timer);
  }, [count, onComplete]);

  return (
    <div className="countdown-overlay">
      <div className="countdown-modal">
        {count > 0 ? (
          <>
            <h2 className="countdown-title">Get Ready!</h2>
            <div className="countdown-number">{count}</div>
          </>
        ) : (
          <div className="countdown-go">GO!</div>
        )}
      </div>
    </div>
  );
}
