from flask import Flask, request, jsonify
from sentence_transformers import SentenceTransformer

app = Flask(__name__)
model = SentenceTransformer('all-MiniLM-L6-v2')

@app.route("/v1/embeddings", methods=["POST"])
def embed():
    try:
        data = request.get_json()
        if not data:
            return jsonify({"error": "Invalid JSON"}), 400

        texts = data.get("input", [])
        if not texts:
            return jsonify({"error": "Input is required"}), 400

        # Garantir que texts seja uma lista
        if isinstance(texts, str):
            texts = [texts]

        embeddings = model.encode(texts).tolist()

        response = {
            "object": "embedding",
            "model": "all-MiniLM-L6-v2",
            "data": [
                {
                    "index": i,
                    "embedding": emb
                } for i, emb in enumerate(embeddings)
            ]
        }
        return jsonify(response)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "model": "all-MiniLM-L6-v2"})

@app.route("/api/tags", methods=["GET"])
def tags():
    return jsonify({
        "models": [
            {
                "name": "all-MiniLM-L6-v2",
                "size": "80MB",
                "dimensions": 384
            }
        ]
    })

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=11434)
