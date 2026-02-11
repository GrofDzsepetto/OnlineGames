const QuizResult = ({
  score,
  total,
}: {
  score: number;
  total: number;
}) => (
  <h2>
    Result: {score} / {total}
  </h2>
);

export default QuizResult;
