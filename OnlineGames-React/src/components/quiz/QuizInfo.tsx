import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { getQuizMeta, getQuizResults } from "../../services/quizService";

type QuizMeta = {
  id: string;
  title: string;
  description: string;
  creator_name?: string;
  isPublic?: boolean;
};

type QuizResultRow = {
  USER_ID: number;
  USER_NAME: string;
  SCORE: number;
  MAX_SCORE: number;
  CREATED_AT: string;
};

const QuizInfo = () => {
  const { slug } = useParams();
  const navigate = useNavigate();

  const [quiz, setQuiz] = useState<QuizMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [results, setResults] = useState<QuizResultRow[]>([]);
  const [resultsLoading, setResultsLoading] = useState(false);
  const [resultsError, setResultsError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) {
      setError("Missing quiz slug");
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);

    getQuizMeta(slug)
      .then(async (meta) => {
        setQuiz(meta);

        // ✅ results betöltés quizId alapján
        if (meta?.id) {
          setResultsLoading(true);
          setResultsError(null);

          try {
            const r = await getQuizResults(meta.id);
            setResults((r.results ?? []) as QuizResultRow[]);
          } catch (e: any) {
            setResults([]);
            setResultsError(e?.message ?? "Failed to load results");
          } finally {
            setResultsLoading(false);
          }
        }
      })
      .catch((e) => setError(e?.message ?? "Failed to load quiz"))
      .finally(() => setLoading(false));
  }, [slug]);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>{error}</div>;
  if (!quiz) return <div>Quiz not found</div>;

  return (
    <div style={{ maxWidth: 700, margin: "40px auto", textAlign: "center" }}>
      <h1>{quiz.title}</h1>

      {quiz.creator_name && (
        <div style={{ fontStyle: "italic", color: "#666" }}>
          Created by {quiz.creator_name}
        </div>
      )}

      <p style={{ marginTop: 20 }}>{quiz.description}</p>

      <button
        style={{
          marginTop: 30,
          padding: "12px 30px",
          fontSize: 18,
          borderRadius: 8,
          background: "#28a745",
          color: "white",
          border: "none",
          cursor: "pointer",
        }}
        onClick={() => navigate(`/play/${slug}`)}
      >
        ▶ Start Quiz
      </button>

      {/* ✅ Leaderboard */}
      <div style={{ marginTop: 40, textAlign: "left" }}>
        <h2 style={{ textAlign: "center" }}>🏆 Leaderboard</h2>

        {resultsLoading && <div style={{ textAlign: "center" }}>Loading results...</div>}

        {resultsError && (
          <div style={{ textAlign: "center", color: "crimson" }}>{resultsError}</div>
        )}

        {!resultsLoading && !resultsError && results.length === 0 && (
          <div style={{ textAlign: "center", opacity: 0.8 }}>
            Még nincs eredmény ehhez a kvízhez.
          </div>
        )}

        {!resultsLoading && !resultsError && results.length > 0 && (
          <div style={{ overflowX: "auto" }}>
            <table style={{ width: "100%", borderCollapse: "collapse", marginTop: 14 }}>
              <thead>
                <tr>
                  <th style={{ textAlign: "left", padding: 10, borderBottom: "1px solid #ddd" }}>#</th>
                  <th style={{ textAlign: "left", padding: 10, borderBottom: "1px solid #ddd" }}>User</th>
                  <th style={{ textAlign: "left", padding: 10, borderBottom: "1px solid #ddd" }}>Score</th>
                  <th style={{ textAlign: "left", padding: 10, borderBottom: "1px solid #ddd" }}>When</th>
                </tr>
              </thead>
              <tbody>
                {results.slice(0, 10).map((r, idx) => (
                  <tr key={`${r.USER_ID}-${idx}`}>
                    <td style={{ padding: 10, borderBottom: "1px solid #eee" }}>{idx + 1}</td>
                    <td style={{ padding: 10, borderBottom: "1px solid #eee" }}>{r.USER_NAME}</td>
                    <td style={{ padding: 10, borderBottom: "1px solid #eee" }}>
                      {r.SCORE} / {r.MAX_SCORE}
                    </td>
                    <td style={{ padding: 10, borderBottom: "1px solid #eee" }}>
                      {r.CREATED_AT}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default QuizInfo;
