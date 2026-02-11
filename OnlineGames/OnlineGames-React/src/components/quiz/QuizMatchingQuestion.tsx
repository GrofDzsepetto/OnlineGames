import { useEffect, useMemo, useRef, useState } from "react";
import type { QuizQuestion } from "../../types/quiz";

type Card = {
  id: string;
  text: string;
  correctLeft: string;
};

const shuffle = <T,>(arr: T[]) => {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
};

const QuizMatchingQuestion = ({
  question,
  onAnswer,
}: {
  question: QuizQuestion;
  onAnswer: (correct: boolean) => void;
}) => {
  const pairs = question.pairs ?? [];

  const columns = useMemo(
    () =>
      pairs.map((p, idx) => ({
        id: `col-${idx}`,
        header: p.left || "—",
        leftKey: p.left,
      })),
    [question.id, pairs]
  );

  const initialBank = useMemo<Card[]>(() => {
    const cards: Card[] = [];
    for (const p of pairs) {
      for (const r of p.rights) {
        cards.push({
          id: `${p.left}::${r}`,
          text: r,
          correctLeft: p.left,
        });
      }
    }
    return shuffle(cards);
  }, [question.id, pairs]);

  const [bank, setBank] = useState<Card[]>(initialBank);
  const [placed, setPlaced] = useState<Record<string, Card[]>>({});
  const [dragId, setDragId] = useState<string | null>(null);
  const [activeDrop, setActiveDrop] = useState<string | null>(null);

  const dragEl = useRef<HTMLDivElement | null>(null);
  const dragPointerId = useRef<number | null>(null);

  useEffect(() => {
    setBank(initialBank);
    setPlaced({});
    setDragId(null);
    setActiveDrop(null);
    dragEl.current = null;
    dragPointerId.current = null;
    document.body.classList.remove("is-dragging");
  }, [initialBank, question.id]);

  const findCard = (id: string): Card | null => {
    const b = bank.find((c) => c.id === id);
    if (b) return b;
    for (const gid in placed) {
      const c = (placed[gid] ?? []).find((x) => x.id === id);
      if (c) return c;
    }
    return null;
  };

  const removeEverywhere = (id: string) => {
    setBank((b) => b.filter((c) => c.id !== id));
    setPlaced((p) => {
      const next: Record<string, Card[]> = {};
      for (const gid in p) {
        next[gid] = (p[gid] ?? []).filter((c) => c.id !== id);
      }
      return next;
    });
  };

  const placeToGroup = (gid: string) => {
    if (!dragId) return;
    const card = findCard(dragId);
    if (!card) return;

    removeEverywhere(dragId);
    setPlaced((p) => ({
      ...p,
      [gid]: [...(p[gid] ?? []), card],
    }));
  };

  const placeToBank = () => {
    if (!dragId) return;
    const card = findCard(dragId);
    if (!card) return;

    removeEverywhere(dragId);
    setBank((b) => [...b, card]);
  };

  const cleanupDrag = () => {
    dragEl.current?.classList.remove("is-dragging");
    document.body.classList.remove("is-dragging");

    if (dragEl.current && dragPointerId.current != null) {
      try {
        dragEl.current.releasePointerCapture(dragPointerId.current);
      } catch {
      }
    }

    dragEl.current = null;
    dragPointerId.current = null;
    setDragId(null);
    setActiveDrop(null);
  };

  const updateActiveDropFromPoint = (clientX: number, clientY: number) => {
    const el = document.elementFromPoint(clientX, clientY);
    const drop = el?.closest("[data-drop]") as HTMLElement | null;
    const dropId = drop?.getAttribute("data-drop");
    setActiveDrop(dropId ?? null);
  };

  const onPointerDown = (e: React.PointerEvent<HTMLDivElement>, id: string) => {
    e.preventDefault();

    setDragId(id);
    dragEl.current = e.currentTarget;
    dragPointerId.current = e.pointerId;

    document.body.classList.add("is-dragging");
    e.currentTarget.classList.add("is-dragging");

    try {
      e.currentTarget.setPointerCapture(e.pointerId);
    } catch {
    }

    updateActiveDropFromPoint(e.clientX, e.clientY);
  };

  const onPointerMove = (e: React.PointerEvent) => {
    if (!dragId) return;
    e.preventDefault();
    updateActiveDropFromPoint(e.clientX, e.clientY);
  };

  const onPointerUp = () => {
    if (!dragId) return;

    if (activeDrop === "bank") placeToBank();
    else if (activeDrop) placeToGroup(activeDrop);
    cleanupDrag();
  };

  const onPointerCancel = () => {
    cleanupDrag();
  };

  const allPlaced = Object.values(placed).flat().length === initialBank.length;

  const submit = () => {
    if (!allPlaced) return;

    const ok = columns.every((col) => {
      const cards = placed[col.id] ?? [];
      return cards.every((c) => c.correctLeft === col.leftKey);
    });

    onAnswer(ok);
  };

  return (
    <div className="quiz-question">
      <h3>{question.question}</h3>

      <div
        className="matching-grid"
        style={{
          gridTemplateColumns: `repeat(${columns.length}, minmax(160px, 1fr))`,
        }}
      >
        {columns.map((col) => (
          <div key={col.id} className="matching-col">
            <div className="matching-head">{col.header}</div>

            <div
              className="matching-dropzone"
              data-drop={col.id}
              data-active={activeDrop === col.id}
              onPointerMove={onPointerMove}
              onPointerUp={onPointerUp}
              onPointerCancel={onPointerCancel}
            >
              {(placed[col.id] ?? []).map((card) => (
                <div
                  key={card.id}
                  className="matching-card"
                  onPointerDown={(e) => onPointerDown(e, card.id)}
                  onPointerMove={onPointerMove}
                  onPointerUp={onPointerUp}
                  onPointerCancel={onPointerCancel}
                >
                  {card.text}
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>

      <div className="matching-bank-wrap">
        <div className="matching-bank-title">Válaszok</div>
        <div
          className="matching-bank"
          data-drop="bank"
          data-active={activeDrop === "bank"}
          onPointerMove={onPointerMove}
          onPointerUp={onPointerUp}
          onPointerCancel={onPointerCancel}
        >
          {bank.map((card) => (
            <div
              key={card.id}
              className="matching-card"
              onPointerDown={(e) => onPointerDown(e, card.id)}
              onPointerMove={onPointerMove}
              onPointerUp={onPointerUp}
              onPointerCancel={onPointerCancel}
            >
              {card.text}
            </div>
          ))}
        </div>
      </div>

      <button className="matching-submit" disabled={!allPlaced} onClick={submit}>
        Ellenőrzés
      </button>
    </div>
  );
};

export default QuizMatchingQuestion;
